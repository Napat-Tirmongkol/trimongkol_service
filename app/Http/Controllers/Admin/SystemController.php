<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
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

        // Pending migrations: rows in /database/migrations not present in
        // the migrations table (if the table even exists).
        $pending = [];
        if (Schema::hasTable('migrations')) {
            $applied = DB::table('migrations')->pluck('migration')->all();
            $files = collect(glob(database_path('migrations/*.php')))
                ->map(fn ($p) => pathinfo($p, PATHINFO_FILENAME));
            $pending = $files->diff($applied)->values()->all();
        }

        $lastResult = session('system_result');

        return view('admin.system', compact('info', 'pending', 'lastResult'));
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
