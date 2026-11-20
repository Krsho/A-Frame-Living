<?php
/*
 * Runs before backend/index.php when the API tests start the backend
 * (php -d auto_prepend_file=tests/api/test-bootstrap.php ...).
 *
 * It changes three settings for the test server only, without touching config.php:
 *   - the database becomes aframe_living_test (the real database is never used),
 *     or the name in TEST_DB_NAME (a missing database, to test the 500 error);
 *   - Nominatim becomes the URL in TEST_NOMINATIM_URL (a fake server, or a closed port);
 *   - logs are written to tests/api/logs/ instead of backend/logs/.
 */

require_once __DIR__ . '/../../backend/config/AppConfig.php';

AppConfig::override('db', ['name' => getenv('TEST_DB_NAME') ?: 'aframe_living_test']);
AppConfig::override('nominatim', [
    'base_url' => getenv('TEST_NOMINATIM_URL') ?: 'http://127.0.0.1:9',
    'timeout'  => 2,
]);
AppConfig::override('log_directory', __DIR__ . '/logs');
