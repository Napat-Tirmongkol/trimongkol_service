<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Process\ExecutableFinder;

class SystemController extends Controller
{
    public function index()
    {
        $info = [
            'app_env' => app()->environment(),
            'app_debug' => config('app.debug'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'db_driver' => DB::connection()->getDriverName(),
            'db_database' => DB::connection()->getDatabaseName(),
        ];

        $mailInfo = [
            'mailer' => config('mail.default'),
            'host' => config('mail.mailers.smtp.host'),
            'port' => config('mail.mailers.smtp.port'),
            'encryption' => config('mail.mailers.smtp.encryption'),
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
        ];

        // Pending migrations: rows in /database/migrations not present in
        // the migrations table (if the table even exists).
        $pending = [];
        if (Schema::hasTable('migrations')) {
            $applied = DB::table('migrations')->pluck('migration')->all();
            $files = collect(glob(database_path('migrations/*.php')))
                ->map(fn ($p) => pathinfo($p, PATHINFO_FILENAME));
            $pending = $files->diff($applied)->values()->all();
        }

        $webhookConfigured = ! empty(Setting::get('deploy.webhook_url'));
        $lastResult = session('system_result');

        return view('admin.system', compact('info', 'mailInfo', 'pending', 'webhookConfigured', 'lastResult'));
    }

    public function pull(Request $request)
    {
        $url = Setting::get('deploy.webhook_url');

        if (! $url) {
            return redirect()->route('admin.system')
                ->with('system_result', [
                    'command' => 'pull (Plesk git webhook)',
                    'output' => __('app.admin.system.no_webhook_url'),
                    'exit_code' => 1,
                ]);
        }

        try {
            $response = Http::timeout(60)->withoutVerifying()->post($url);
            $exitCode = $response->successful() ? 0 : 1;
            $output = "HTTP {$response->status()}\n\n" . $response->body();
        } catch (\Throwable $e) {
            $exitCode = 1;
            $output = 'Error: ' . $e->getMessage();
        }

        AuditLog::record('system.pull', null, 'git pull (via Plesk webhook)', [
            'exit_code' => $exitCode,
        ]);

        // Auto-clear caches after a successful pull so updated views / config /
        // routes take effect without a separate manual step.
        if ($exitCode === 0) {
            try {
                $clear = new BufferedOutput();
                Artisan::call('optimize:clear', [], $clear);
                $output .= "\n\n--- caches cleared (optimize:clear) ---\n".$clear->fetch();
            } catch (\Throwable $e) {
                $output .= "\n\n--- cache clear failed: ".$e->getMessage()." ---";
            }
        }

        return redirect()->route('admin.system')
            ->with('system_result', [
                'command' => 'pull (Plesk git webhook)',
                'output' => $output,
                'exit_code' => $exitCode,
            ]);
    }

    public function migrate(Request $request)
    {
        $output = new BufferedOutput();

        // A failing migration throws; catch it so the admin sees the error in
        // the result panel instead of a bare 500 page.
        try {
            $exitCode = Artisan::call('migrate', ['--force' => true], $output);
            $result = $output->fetch();
        } catch (\Throwable $e) {
            $exitCode = 1;
            $result = $output->fetch()."\n\nMigration failed: ".$e->getMessage();
        }

        AuditLog::record('system.migrate', null, 'artisan migrate', [
            'exit_code' => $exitCode,
        ]);

        return redirect()->route('admin.system')
            ->with('system_result', [
                'command' => 'php artisan migrate --force',
                'output' => $result,
                'exit_code' => $exitCode,
            ]);
    }

    public function testEmail(Request $request)
    {
        $data = $request->validate([
            'to' => 'required|email|max:160',
        ]);

        $to = $data['to'];
        $body = "This is a test email from " . config('app.name') . ".\n\n"
              . "If you received this, SMTP is configured correctly.\n\n"
              . "Sent at: " . now()->toDateTimeString() . "\n"
              . "From host: " . request()->getHost() . "\n";

        try {
            Mail::raw($body, function ($mail) use ($to) {
                $mail->to($to)
                     ->subject('Test email — ' . config('app.name'));
            });

            $output = "Mail dispatched to {$to}.\n\n"
                    . "'Dispatched' only means the SMTP server accepted the message. The recipient should check their inbox AND junk/spam folder.\n\n"
                    . "Mail config used:\n"
                    . "  MAIL_MAILER     = " . config('mail.default') . "\n"
                    . "  MAIL_HOST       = " . config('mail.mailers.smtp.host') . "\n"
                    . "  MAIL_PORT       = " . config('mail.mailers.smtp.port') . "\n"
                    . "  MAIL_ENCRYPTION = " . (config('mail.mailers.smtp.encryption') ?: '(none)') . "\n"
                    . "  MAIL_FROM       = " . config('mail.from.address');
            $exitCode = 0;
        } catch (\Throwable $e) {
            $output = "Send failed.\n\n" . $e->getMessage();
            $exitCode = 1;
        }

        AuditLog::record('system.test_email', null, $to, [
            'ok' => $exitCode === 0,
            'error' => $exitCode === 0 ? null : substr($e->getMessage() ?? '', 0, 500),
        ]);

        return redirect()->route('admin.system')
            ->with('system_result', [
                'command' => "Test email to {$to}",
                'output' => $output,
                'exit_code' => $exitCode,
            ]);
    }

    public function clearCache(Request $request)
    {
        $output = new BufferedOutput();
        $exitCode = Artisan::call('optimize:clear', [], $output);

        AuditLog::record('system.clear_cache', null, 'artisan optimize:clear', [
            'exit_code' => $exitCode,
        ]);

        return redirect()->route('admin.system')
            ->with('system_result', [
                'command' => 'php artisan optimize:clear',
                'output' => $output->fetch(),
                'exit_code' => $exitCode,
            ]);
    }

    /**
     * Rebuild the front-end asset bundle (Tailwind CSS + JS). New utility
     * classes only land in production once `vite build` runs, so this
     * exposes the npm script through the admin UI for Plesk hosts that
     * don't have SSH.
     */
    public function buildAssets(Request $request)
    {
        try {
            $npm = $this->findNpmBinary();
        } catch (\Throwable $e) {
            $npm = null;
            Log::warning('findNpmBinary failed', ['error' => $e->getMessage()]);
        }

        if (! $npm) {
            return redirect()->route('admin.system')
                ->with('system_result', [
                    'command' => 'npm run build',
                    'output' => "Could not locate the `npm` executable on this server.\n\n"
                              . "Tried: PATH (via Symfony ExecutableFinder), /usr/local/bin, /usr/bin,\n"
                              . "/opt/plesk/node/*/bin.\n\n"
                              . "Either install Node.js on the host or run `npm run build` over SSH /\n"
                              . "the Plesk Node.js panel.",
                    'exit_code' => 127,
                ]);
        }

        try {
            $result = Process::path(base_path())
                ->timeout(180)
                ->run([$npm, 'run', 'build']);

            $exitCode = $result->exitCode() ?? 1;
            $output = trim($result->output() . "\n" . $result->errorOutput());
            if ($output === '') {
                $output = '(no output)';
            }
        } catch (\Throwable $e) {
            $exitCode = 1;
            $output = "Process failed: " . $e->getMessage()
                . "\n\nLikely cause: `proc_open` is disabled on this host. Ask the host to\n"
                . "allow it, or run `npm run build` over SSH instead.";
        }

        AuditLog::record('system.build_assets', null, 'npm run build', [
            'exit_code' => $exitCode,
            'npm_path' => $npm,
        ]);

        return redirect()->route('admin.system')
            ->with('system_result', [
                'command' => 'npm run build',
                'output' => $output,
                'exit_code' => $exitCode,
            ]);
    }

    private function findNpmBinary(): ?string
    {
        $candidates = [];

        // 1. Whatever's on PATH (works when the web user has a sane PATH).
        try {
            $found = (new ExecutableFinder())->find('npm');
            if ($found) {
                $candidates[] = $found;
            }
        } catch (\Throwable) {
            // ExecutableFinder may internally try proc/exec — fall through.
        }

        // 2. Common absolute locations.
        $candidates[] = '/usr/local/bin/npm';
        $candidates[] = '/usr/bin/npm';

        // 3. Plesk-managed Node.js installs (one binary per major version).
        foreach (glob('/opt/plesk/node/*/bin/npm') ?: [] as $plesk) {
            $candidates[] = $plesk;
        }

        foreach (array_unique($candidates) as $path) {
            if (@is_file($path) && @is_executable($path)) {
                return $path;
            }
        }
        return null;
    }
}
