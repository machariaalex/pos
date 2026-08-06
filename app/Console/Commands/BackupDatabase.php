<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database {--keep-days=30 : Delete backups older than this many days}';

    protected $description = 'Dump the database to storage/app/backups, ready to ship offsite';

    public function handle(): int
    {
        $backupDir = storage_path('app/backups');
        File::ensureDirectoryExists($backupDir);

        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");
        $timestamp = Carbon::now()->format('Y-m-d_His');

        try {
            $path = match ($driver) {
                'sqlite' => $this->backupSqlite($connection, $backupDir, $timestamp),
                'mysql' => $this->backupMysql($connection, $backupDir, $timestamp),
                'pgsql' => $this->backupPgsql($connection, $backupDir, $timestamp),
                default => throw new RuntimeException("No backup strategy for database driver [{$driver}]."),
            };
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Backup written to {$path}");

        $this->pruneOldBackups($backupDir, (int) $this->option('keep-days'));

        return self::SUCCESS;
    }

    private function backupSqlite(string $connection, string $backupDir, string $timestamp): string
    {
        $source = config("database.connections.{$connection}.database");

        if (! $source || ! File::exists($source)) {
            throw new RuntimeException("SQLite database file not found: {$source}");
        }

        $destination = "{$backupDir}/agrovet-{$timestamp}.sqlite";
        File::copy($source, $destination);

        return $destination;
    }

    private function backupMysql(string $connection, string $backupDir, string $timestamp): string
    {
        $config = config("database.connections.{$connection}");
        $destination = "{$backupDir}/agrovet-{$timestamp}.sql";

        $process = new Process([
            'mysqldump',
            '--host='.$config['host'],
            '--port='.($config['port'] ?? 3306),
            '--user='.$config['username'],
            '--single-transaction',
            '--skip-lock-tables',
            $config['database'],
        ]);

        // Password via env var, not argv, so it never shows up in `ps aux`.
        $process->setEnv(['MYSQL_PWD' => $config['password']]);
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('mysqldump failed: '.$process->getErrorOutput());
        }

        File::put($destination, $process->getOutput());

        $gzipped = "{$destination}.gz";
        File::put($gzipped, gzencode(File::get($destination)));
        File::delete($destination);

        return $gzipped;
    }

    private function backupPgsql(string $connection, string $backupDir, string $timestamp): string
    {
        $config = config("database.connections.{$connection}");
        $destination = "{$backupDir}/agrovet-{$timestamp}.sql";

        $process = new Process([
            'pg_dump',
            '--host='.$config['host'],
            '--port='.($config['port'] ?? 5432),
            '--username='.$config['username'],
            '--no-password',
            '--format=plain',
            $config['database'],
        ]);

        $process->setEnv(['PGPASSWORD' => $config['password']]);
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('pg_dump failed: '.$process->getErrorOutput());
        }

        File::put($destination, $process->getOutput());

        $gzipped = "{$destination}.gz";
        File::put($gzipped, gzencode(File::get($destination)));
        File::delete($destination);

        return $gzipped;
    }

    private function pruneOldBackups(string $backupDir, int $keepDays): void
    {
        $cutoff = Carbon::now()->subDays($keepDays);

        foreach (File::files($backupDir) as $file) {
            if (Carbon::createFromTimestamp($file->getMTime())->lt($cutoff)) {
                File::delete($file->getPathname());
            }
        }
    }
}
