<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Database\Migrations\Migrator;

class WeaverSmoke extends Command
{
    protected $signature = 'weaver:smoke {--demo : Create or reuse a demo user (local only)}';
    protected $description = 'Local environment smoke check (DB, migrations, routes, counts).';

    public function handle(): int
    {
        $env = app()->environment();
        if ($env !== 'local') {
            $this->line(json_encode([
                'ok' => false,
                'reason' => 'environment not local',
                'env' => $env,
            ]));
            return self::FAILURE;
        }

        $appUrl = config('app.url');
        $driver = config('database.default');
        $dbReachable = false;
        try {
            DB::select('select 1');
            $dbReachable = true;
        } catch (\Throwable $e) {
            $dbReachable = false;
        }

        /** @var Migrator $migrator */
        $migrator = app('migrator');
        $migrationsOk = false;
        try {
            if ($migrator->repositoryExists()) {
                $paths = $migrator->paths();
                $files = $migrator->getMigrationFiles(database_path('migrations'));
                // repository stores ran migrations
                $ran = $migrator->getRepository()->getRan();
                $pending = array_diff(array_keys($files), $ran);
                $migrationsOk = count($pending) === 0;
            }
        } catch (\Throwable $e) {
            $migrationsOk = false;
        }

        $userCount = null;
        $projectCount = null;
        if ($dbReachable && $migrationsOk) {
            try { $userCount = User::count(); } catch (\Throwable $e) { $userCount = null; }
            try { $projectCount = Project::count(); } catch (\Throwable $e) { $projectCount = null; }
        }

        $needUris = ['/login','/register','/dashboard','/project/setup'];
        $routes = [];
        foreach ($needUris as $uri) { $routes[$uri] = false; }
        foreach (Route::getRoutes() as $route) {
            $uri = '/' . ltrim($route->uri(), '/');
            if (array_key_exists($uri, $routes)) { $routes[$uri] = true; }
        }

        $demoInfo = null;
        if ($this->option('demo')) {
            try {
                $demo = User::firstOrCreate([
                    'email' => 'demo@local.test',
                ], [
                    'name' => 'Demo User',
                    'password' => Hash::make('password'),
                ]);
                $demoInfo = [
                    'created' => $demo->wasRecentlyCreated,
                    'email' => 'demo@local.test',
                    'password_hint' => 'password',
                    'next' => '/project/setup',
                ];
            } catch (\Throwable $e) {
                $demoInfo = [ 'error' => 'failed to create demo user' ];
            }
        }

        $ok = $dbReachable && $migrationsOk && !in_array(false, array_values($routes), true);

        $payload = [
            'ok' => $ok,
            'app_url' => $appUrl,
            'env' => $env,
            'db' => [ 'driver' => $driver, 'reachable' => $dbReachable ],
            'migrations_ok' => $migrationsOk,
            'counts' => [ 'users' => $userCount, 'projects' => $projectCount ],
            'routes' => $routes,
        ];
        if ($demoInfo !== null) { $payload['demo_user'] = $demoInfo; }

        $this->line(json_encode($payload, JSON_UNESCAPED_SLASHES));
        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
