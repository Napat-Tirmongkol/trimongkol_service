<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Output\BufferedOutput;

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
        $exitCode = Artisan::call('migrate', ['--force' => true], $output);

        AuditLog::record('system.migrate', null, 'artisan migrate', [
            'exit_code' => $exitCode,
        ]);

        return redirect()->route('admin.system')
            ->with('system_result', [
                'command' => 'php artisan migrate --force',
                'output' => $output->fetch(),
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
}
