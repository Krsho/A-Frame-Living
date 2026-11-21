<?php
/*
 * AppConfig – reads backend/config/config.php once and gives each part of the
 * backend the settings it needs:
 *
 *   AppConfig::get('db')          // database connection
 *   AppConfig::get('nominatim')   // external API (OpenStreetMap Nominatim)
 *
 * config.php is git-ignored (it holds passwords and secrets);
 * config.example.php is the committed template.
 */

class AppConfig
{
    private static ?array $values = null;

    /** Default values for optional sections, so an older config.php still works. */
    private const DEFAULTS = [
        'nominatim' => [
            'base_url'   => 'https://nominatim.openstreetmap.org',
            'user_agent' => 'AFrameLiving/1.0 (university project)',
            'email'      => '',
            'timeout'    => 5,
        ],
        'log_directory' => __DIR__ . '/../logs',
    ];

    /** Returns one section of the configuration, e.g. AppConfig::get('db'). */
    public static function get(string $key): mixed
    {
        $values = self::all();
        if (!array_key_exists($key, $values)) {
            throw new RuntimeException("Missing configuration value '{$key}' in backend/config/config.php.");
        }
        return $values[$key];
    }

    /**
     * Replaces some settings while the program runs. Used by the tests,
     * e.g. AppConfig::override('db', ['name' => 'aframe_living_test']).
     */
    public static function override(string $key, mixed $value): void
    {
        $values = self::all();
        $current = $values[$key] ?? null;
        self::$values[$key] = is_array($current) && is_array($value) ? array_merge($current, $value) : $value;
    }

    private static function all(): array
    {
        if (self::$values === null) {
            $file = __DIR__ . '/config.php';
            if (!is_file($file)) {
                throw new RuntimeException(
                    'Missing backend/config/config.php. Copy config.example.php to config.php and fill in your settings.'
                );
            }
            $fromFile = require $file;

            // Sections from config.php win; missing sections fall back to the defaults.
            self::$values = self::DEFAULTS;
            foreach ($fromFile as $key => $value) {
                $default = self::DEFAULTS[$key] ?? null;
                self::$values[$key] = is_array($default) && is_array($value) ? array_merge($default, $value) : $value;
            }
        }
        return self::$values;
    }
}
