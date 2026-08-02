<?php

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BackupDatabaseTest extends TestCase
{
    private string $backupDir;

    private string $sqliteSource;

    protected function setUp(): void
    {
        parent::setUp();

        // The test suite runs against an in-memory SQLite DB (no file to
        // copy), so point the connection at a real temp file just for this test.
        $this->sqliteSource = tempnam(sys_get_temp_dir(), 'agrovet_backup_test').'.sqlite';
        touch($this->sqliteSource);
        config(['database.connections.sqlite.database' => $this->sqliteSource]);

        $this->backupDir = storage_path('app/backups');
    }

    protected function tearDown(): void
    {
        @unlink($this->sqliteSource);

        foreach (File::glob("{$this->backupDir}/agrovet-*.sqlite") as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    public function test_backup_creates_a_copy_of_the_sqlite_database(): void
    {
        $exitCode = Artisan::call('backup:database');

        $this->assertSame(0, $exitCode);
        $this->assertNotEmpty(File::glob("{$this->backupDir}/agrovet-*.sqlite"));
    }

    public function test_backup_prunes_files_older_than_the_retention_window(): void
    {
        File::ensureDirectoryExists($this->backupDir);
        $oldFile = "{$this->backupDir}/agrovet-old-test.sqlite";
        File::put($oldFile, 'stale backup content');
        touch($oldFile, now()->subDays(40)->timestamp);

        Artisan::call('backup:database', ['--keep-days' => 30]);

        $this->assertFileDoesNotExist($oldFile);
    }

    public function test_recent_backups_are_not_pruned(): void
    {
        Artisan::call('backup:database');

        $created = File::glob("{$this->backupDir}/agrovet-*.sqlite");
        $this->assertNotEmpty($created);
        $this->assertFileExists($created[0]);
    }
}
