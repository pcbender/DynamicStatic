<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class WeaverPrepareDevDb extends Command
{
    protected $signature = 'weaver:dev:prepare-db {--fresh : Drop all tables and re-run migrations}';
    protected $description = 'Ensure SQLite dev database file exists and run migrations';

    public function handle(): int
    {
        $connection = config('database.default');
        if ($connection !== 'sqlite') {
            $this->warn('Current DB connection is not sqlite ('. $connection .'). Skipping file creation.');
        } else {
            $path = database_path('database.sqlite');
            if (!File::exists($path)) {
                File::ensureDirectoryExists(dirname($path));
                File::put($path, '');
                $this->info('Created SQLite database file: '. $path);
            } else {
                $this->line('SQLite database file already exists.');
            }
        }

        if ($this->option('fresh')) {
            Artisan::call('migrate:fresh', ['--force' => true]);
        } else {
            Artisan::call('migrate', ['--force' => true]);
        }
        $this->output->write(Artisan::output());
        return self::SUCCESS;
    }
}
