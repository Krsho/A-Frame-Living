<?php
/*
 * Database – creates the single PDO connection used by all DAO classes.
 *
 * The connection settings are read from backend/config/config.php
 * (git-ignored; see config.example.php for the template).
 */

class Database
{
    private static ?PDO $connection = null;
    private static ?array $settings = null;

    /** Returns the shared PDO connection, creating it on first use. */
    public static function connection(): PDO
    {
        if (self::$connection === null) {
            $db = self::settings();
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $db['host'],
                $db['port'],
                $db['name'],
                $db['charset']
            );

            self::$connection = new PDO($dsn, $db['user'], $db['password'], [
                // Throw an exception on every SQL error instead of failing silently.
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                // Return rows as ['column' => value] arrays.
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // Use real prepared statements on the MySQL server.
                PDO::ATTR_EMULATE_PREPARES => false,
                // Strict mode: MySQL rejects invalid values (e.g. an unknown ENUM value)
                // instead of silently saving an empty string. XAMPP does not enable it by default.
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET SESSION sql_mode = 'STRICT_ALL_TABLES,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION'",
            ]);
        }

        return self::$connection;
    }

    /**
     * Overrides some connection settings, e.g. ['name' => 'aframe_living_test'] in the tests.
     * Closes the current connection so the next call to connection() uses the new settings.
     */
    public static function useSettings(array $overrides): void
    {
        self::$settings = array_merge(self::settings(), $overrides);
        self::$connection = null;
    }

    /** The 'db' part of config.php. */
    public static function settings(): array
    {
        if (self::$settings === null) {
            $file = __DIR__ . '/config.php';
            if (!is_file($file)) {
                throw new RuntimeException(
                    'Missing backend/config/config.php. Copy config.example.php to config.php and fill in your database settings.'
                );
            }
            $config = require $file;
            self::$settings = $config['db'];
        }

        return self::$settings;
    }
}
