<?php
/*
 * Logger – writes one line per event to a log file in backend/logs/:
 *
 *   info.log   normal events, e.g. calls to the external API
 *   error.log  problems, e.g. unhandled exceptions, database errors, external API failures
 *
 * Example line:
 *   [2026-11-20 14:05:12] ERROR Nominatim request failed: timeout after 5 s (city="Kupres")
 *
 * Milestone 4 adds request logging (every request with user and role) on top of this.
 * The logs folder is git-ignored.
 */

class Logger
{
    public static function info(string $message): void
    {
        self::write('info.log', 'INFO', $message);
    }

    public static function error(string $message): void
    {
        self::write('error.log', 'ERROR', $message);
    }

    /** Short description of an exception: class, message and the file:line where it happened (no full stack trace). */
    public static function describe(Throwable $e): string
    {
        return sprintf('%s: %s (%s:%d)', get_class($e), $e->getMessage(), basename($e->getFile()), $e->getLine());
    }

    private static function write(string $file, string $level, string $message): void
    {
        $directory = AppConfig::get('log_directory');
        if (!is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        // Keep each entry on one line, so the log is easy to read and search.
        $message = str_replace(["\r", "\n"], ' ', $message);
        $line = sprintf('[%s] %s %s', date('Y-m-d H:i:s'), $level, $message) . PHP_EOL;

        // LOCK_EX: two requests at the same time cannot mix their lines.
        // The @ hides a PHP warning if the file cannot be written; logging must never break a request.
        @file_put_contents($directory . '/' . $file, $line, FILE_APPEND | LOCK_EX);
    }
}
