<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * NIGHTLY DATABASE BACKUP (R7 — absorbed from the retired "Phase 6").
 *
 * The system is the record of the university's extension work; losing it means
 * losing the evidence base. This command writes a timestamped dump and prunes
 * the old ones, so the scheduled run is the whole backup story — no cron script
 * to keep in sync with `.env`.
 *
 * DRIVER-AWARE
 * ------------
 * - **mysql / mariadb** — `mysqldump` with `--single-transaction --skip-lock-tables`
 *   so a live system is dumped consistently without locking every table. The
 *   password goes through `MYSQL_PWD` rather than argv, so it never appears in
 *   the process list. Output is written straight to the target file by
 *   `--result-file`, so a large dump never sits in PHP memory.
 * - **sqlite** — `VACUUM INTO`, which is atomic. Copying the file while the app
 *   writes to it can produce a torn backup; `VACUUM INTO` cannot.
 * - **`:memory:`** — nothing to dump. Warns and exits SUCCESS, so a test suite
 *   running on an in-memory database never fails a scheduled backup.
 *
 * FAILURES ARE LOUD
 * -----------------
 * A backup that silently stops happening is worse than none, because it is
 * trusted. Any failure exits FAILURE and writes to the log, so the scheduler's
 * output is a real signal. The dump is written to a `.tmp` path and only renamed
 * into place on success, so a partial file can never be mistaken for a good one.
 */
class BackupDatabase extends Command
{
    protected $signature = 'smartcemes:backup-database
                            {--keep= : How many backups to retain (0 keeps every backup; default from config)}
                            {--path= : Override the destination directory}';

    protected $description = 'Write a timestamped database dump and prune backups beyond the retention count';

    public function handle(): int
    {
        $directory = $this->backupDirectory();

        // `database.default` is a CONNECTION NAME, not a driver — read the
        // driver off the connection. (Getting this wrong makes every backup
        // silently report "nothing to back up".)
        $connectionName = (string) config('database.default');
        $connection = (array) config("database.connections.{$connectionName}", []);
        $driver = (string) ($connection['driver'] ?? $connectionName);

        try {
            File::ensureDirectoryExists($directory);

            $file = match ($driver) {
                'mysql', 'mariadb' => $this->dumpMysql($connection, $directory),
                'sqlite' => $this->dumpSqlite($connection, $directory),
                default => null,
            };
        } catch (\Throwable $e) {
            $this->error('Backup failed: '.$e->getMessage());
            Log::error('Database backup failed', [
                'driver' => $driver,
                'database' => $connection['database'] ?? null,
                'exception' => $e,
            ]);

            return self::FAILURE;
        }

        if ($file === null) {
            $this->warn("Nothing to back up — the '{$driver}' connection has no file to dump.");

            return self::SUCCESS;
        }

        $this->info('Backup written: '.$file.' ('.$this->humanSize(File::size($file)).')');

        $pruned = $this->prune($directory, $this->retention());

        if ($pruned > 0) {
            $this->info("Pruned {$pruned} backup(s) beyond the retention count.");
        }

        return self::SUCCESS;
    }

    /** `--keep` when given, otherwise the configured retention. */
    private function retention(): int
    {
        $option = $this->option('keep');

        $keep = ($option === null || $option === '')
            ? (int) config('smartcemes.backup.keep', 14)
            : (int) $option;

        return max(0, $keep);
    }

    /* ------------------------------------------------------------------ */
    /* Dumpers */
    /* ------------------------------------------------------------------ */

    private function dumpMysql(array $connection, string $directory): string
    {
        $target = $this->targetPath($directory, 'sql');
        $temporary = $target.'.tmp';

        // mysqldump writes the dump itself — see the class docblock.
        $process = new Process(
            $this->mysqldumpArguments($connection, $temporary),
            null,
            ['MYSQL_PWD' => (string) ($connection['password'] ?? '')],
        );
        $process->setTimeout(600);
        $process->run();

        if (! $process->isSuccessful()) {
            File::delete($temporary);

            throw new \RuntimeException(
                trim($process->getErrorOutput()) ?: 'mysqldump exited with code '.$process->getExitCode()
            );
        }

        // A dump that produced no bytes is not a backup.
        if (! File::exists($temporary) || File::size($temporary) === 0) {
            File::delete($temporary);

            throw new \RuntimeException('mysqldump reported success but produced an empty file.');
        }

        File::move($temporary, $target);

        return $target;
    }

    private function dumpSqlite(array $connection, string $directory): ?string
    {
        $source = (string) ($connection['database'] ?? '');

        // `:memory:` (and a missing file) is a dev/test artefact, not a database
        // whose loss matters — warn rather than fail the scheduled run.
        if ($source === '' || $source === ':memory:' || ! File::exists($source)) {
            return null;
        }

        $target = $this->targetPath($directory, 'sqlite');
        $temporary = $target.'.tmp';

        // VACUUM INTO is atomic; a plain file copy of a live database can tear.
        DB::statement('VACUUM INTO '.DB::getPdo()->quote($temporary));

        if (! File::exists($temporary) || File::size($temporary) === 0) {
            File::delete($temporary);

            throw new \RuntimeException('VACUUM INTO reported success but produced an empty file.');
        }

        File::move($temporary, $target);

        return $target;
    }

    /**
     * The `mysqldump` argv for a connection.
     *
     * Extracted so the argument set can be asserted without a MySQL server
     * present — the suite runs on SQLite, so the MySQL path is otherwise
     * untestable.
     *
     * @param  array<string, mixed>  $connection
     * @return array<int, string>
     */
    public function mysqldumpArguments(array $connection, string $file): array
    {
        // Configurable: a Windows box or shared host often keeps mysqldump
        // outside the PATH, and the default then fails at 02:00 with
        // "'mysqldump' is not recognized".
        $binary = (string) config('smartcemes.backup.dump_binary', 'mysqldump');

        return [
            $binary,
            '--host='.($connection['host'] ?? '127.0.0.1'),
            '--port='.($connection['port'] ?? '3306'),
            '--user='.($connection['username'] ?? 'root'),
            // Consistent dump without locking every table — the right trade-off
            // for a live system whose tables are InnoDB.
            '--single-transaction',
            '--skip-lock-tables',
            // Stream rows rather than buffering the whole result set.
            '--quick',
            // Stored routines are part of the schema being protected.
            '--routines',
            '--result-file='.$file,
            (string) ($connection['database'] ?? ''),
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Retention */
    /* ------------------------------------------------------------------ */

    /**
     * Keep the newest `$keep` backups, delete the rest.
     *
     * @return int how many files were deleted
     */
    public function prune(string $directory, int $keep): int
    {
        if ($keep === 0) {
            return 0;
        }

        $backups = collect(File::files($directory))
            ->filter(fn (\SplFileInfo $file) => $this->isBackup($file->getFilename()))
            ->sortByDesc(fn (\SplFileInfo $file) => $file->getMTime())
            ->values();

        $stale = $backups->slice($keep);

        $stale->each(fn (\SplFileInfo $file) => File::delete($file->getPathname()));

        return $stale->count();
    }

    private function isBackup(string $filename): bool
    {
        return (bool) preg_match('/^smartcemes_[A-Za-z0-9_-]+_\d{4}-\d{2}-\d{2}_\d{6}\.(sql|sqlite)$/', $filename);
    }

    /* ------------------------------------------------------------------ */
    /* Paths */
    /* ------------------------------------------------------------------ */

    public function backupDirectory(): string
    {
        $override = $this->option('path');

        if (is_string($override) && $override !== '') {
            return rtrim($override, '/\\');
        }

        $configured = config('smartcemes.backup.directory');

        if (is_string($configured) && $configured !== '') {
            return rtrim($configured, '/\\');
        }

        return storage_path('app/backups');
    }

    private function targetPath(string $directory, string $extension): string
    {
        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database", 'database');
        $slug = preg_replace('/[^A-Za-z0-9_-]/', '_', basename($database)) ?: 'database';

        // `His`, not `H:i:s`: a colon is illegal in a Windows filename, and this
        // app is developed on Windows.
        return sprintf(
            '%s/smartcemes_%s_%s.%s',
            $directory,
            $slug,
            now()->format('Y-m-d_His'),
            $extension,
        );
    }

    private function humanSize(int $bytes): string
    {
        return $bytes >= 1048576
            ? round($bytes / 1048576, 1).' MB'
            : round($bytes / 1024, 1).' KB';
    }
}
