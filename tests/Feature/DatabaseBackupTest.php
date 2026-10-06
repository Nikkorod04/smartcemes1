<?php

namespace Tests\Feature;

use App\Console\Commands\BackupDatabase;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * The nightly database backup (R7, absorbed from the retired "Phase 6").
 *
 * The backup is the only protection against losing the university's extension
 * record, so the things worth pinning are the ones that make it TRUSTWORTHY:
 * that it is actually scheduled, that a failure is loud, that a partial file can
 * never be mistaken for a good one, and that the password never reaches argv.
 *
 * The suite runs on in-memory SQLite, so the MySQL path is asserted through the
 * extracted argv builder rather than by running `mysqldump`.
 */
class DatabaseBackupTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = storage_path('framework/testing/backups-'.uniqid());
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);

        parent::tearDown();
    }

    public function test_the_backup_is_scheduled_nightly(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains((string) $event->command, 'smartcemes:backup-database'));

        $this->assertNotNull($event, 'The nightly backup must be registered in routes/console.php.');
        $this->assertSame('0 2 * * *', $event->expression, 'The backup must run nightly at 02:00.');
    }

    /**
     * A test suite (or a dev machine) runs on `:memory:`. That must not fail the
     * scheduled run — there is simply nothing to dump.
     */
    public function test_an_in_memory_database_warns_rather_than_failing(): void
    {
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);

        $this->artisan('smartcemes:backup-database', ['--path' => $this->directory])
            ->expectsOutputToContain('Nothing to back up')
            ->assertExitCode(0);

        $this->assertCount(0, File::glob($this->directory.'/*'), 'Nothing should be written for :memory:.');
    }

    /**
     * A real SQLite file is dumped with `VACUUM INTO`, which is atomic — and the
     * `.tmp` staging file must not survive into the backup set.
     */
    public function test_a_sqlite_database_is_dumped_atomically(): void
    {
        $source = storage_path('framework/testing/backup-source-'.uniqid().'.sqlite');
        File::ensureDirectoryExists(dirname($source));
        touch($source);

        config(['database.connections.backup_test' => [
            'driver' => 'sqlite',
            'database' => $source,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);
        config(['database.default' => 'backup_test']);
        DB::purge('backup_test');

        $this->artisan('smartcemes:backup-database', ['--path' => $this->directory, '--keep' => 0])
            ->assertExitCode(0);

        $backups = File::glob($this->directory.'/smartcemes_*.sqlite');

        $this->assertCount(1, $backups, 'Exactly one backup file should be written.');
        $this->assertGreaterThan(0, File::size($backups[0]), 'A zero-byte dump is not a backup.');

        // The staging file is renamed into place, never left behind.
        $this->assertCount(0, File::glob($this->directory.'/*.tmp'), 'A partial .tmp file was left behind.');

        File::delete($source);
    }

    /**
     * The password is passed via `MYSQL_PWD`, never argv — argv is world-readable
     * in the process list.
     */
    public function test_the_mysqldump_arguments_are_complete_and_never_carry_the_password(): void
    {
        $arguments = (new BackupDatabase)->mysqldumpArguments([
            'host' => '127.0.0.1',
            'port' => 3306,
            'username' => 'root',
            'password' => 'super-secret',
            'database' => 'smartcemes_v4',
        ], '/tmp/dump.sql');

        foreach ([
            '--single-transaction',   // consistent dump without locking
            '--skip-lock-tables',
            '--quick',                // stream rows, don't buffer them
            '--routines',             // stored routines are part of the schema
            '--result-file=/tmp/dump.sql',
            '--host=127.0.0.1',
            '--port=3306',
            '--user=root',
            'smartcemes_v4',
        ] as $expected) {
            $this->assertContains($expected, $arguments);
        }

        $this->assertStringNotContainsString(
            'super-secret',
            implode(' ', $arguments),
            'The database password must not appear in argv.'
        );
    }

    public function test_pruning_keeps_the_newest_backups_and_ignores_other_files(): void
    {
        File::ensureDirectoryExists($this->directory);

        // Five backups, with unambiguous ages (mtime resolution is one second).
        foreach (range(1, 5) as $age) {
            $path = $this->directory."/smartcemes_smartcemes_v4_2026-09-0{$age}_020000.sql";
            File::put($path, "dump {$age}");
            touch($path, now()->subDays(10 - $age)->getTimestamp());
        }

        File::put($this->directory.'/notes.txt', 'not a backup');

        $pruned = (new BackupDatabase)->prune($this->directory, 2);

        $this->assertSame(3, $pruned);

        $remaining = collect(File::glob($this->directory.'/smartcemes_*.sql'))
            ->map(fn (string $path) => basename($path))
            ->sort()
            ->values()
            ->all();

        // Days 4 and 5 are the newest two.
        $this->assertSame([
            'smartcemes_smartcemes_v4_2026-09-04_020000.sql',
            'smartcemes_smartcemes_v4_2026-09-05_020000.sql',
        ], $remaining);

        $this->assertTrue(File::exists($this->directory.'/notes.txt'), 'Non-backup files must be left alone.');
    }

    public function test_a_zero_retention_setting_keeps_every_backup(): void
    {
        File::ensureDirectoryExists($this->directory);

        foreach (range(1, 3) as $age) {
            File::put($this->directory."/smartcemes_db_2026-09-0{$age}_020000.sql", "dump {$age}");
        }

        $this->assertSame(0, (new BackupDatabase)->prune($this->directory, 0));
        $this->assertCount(3, File::glob($this->directory.'/smartcemes_*.sql'));
    }

    /**
     * The filename must be Windows-safe. A colon is illegal in a Windows
     * filename (and this app is developed on Windows), and the database name has
     * to be sanitised before it becomes part of the slug.
     */
    public function test_the_backup_filename_is_windows_safe_and_prunable(): void
    {
        // A source name carrying characters that need sanitising.
        $source = storage_path('framework/testing/backup source '.uniqid().'.sqlite');
        File::ensureDirectoryExists(dirname($source));
        touch($source);

        config(['database.connections.backup_test' => ['driver' => 'sqlite', 'database' => $source]]);
        config(['database.default' => 'backup_test']);
        DB::purge('backup_test');

        $this->artisan('smartcemes:backup-database', ['--path' => $this->directory, '--keep' => 0])
            ->assertExitCode(0);

        $matches = File::glob($this->directory.'/smartcemes_*.sqlite');
        $this->assertCount(1, $matches);

        $file = basename($matches[0]);

        $this->assertDoesNotMatchRegularExpression('/[:*?"<>| ]/', $file, 'The filename must contain no illegal characters.');
        $this->assertMatchesRegularExpression(
            '/^smartcemes_[A-Za-z0-9_-]+_\d{4}-\d{2}-\d{2}_\d{6}\.sqlite$/',
            $file,
            'The filename must match the pattern the pruner recognises — otherwise it is never pruned.'
        );

        File::delete($source);
    }
}
