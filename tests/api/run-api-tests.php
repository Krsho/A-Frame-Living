<?php
/*
 * API tests (Milestone 3).
 *
 *   php tests/api/run-api-tests.php
 *
 * 1. Rebuilds the test database `aframe_living_test` from backend/database/aframe_living.sql
 *    (the real `aframe_living` database is never touched).
 * 2. Starts four local servers with PHP's built-in server:
 *      - a fake Nominatim (tests/api/fake-nominatim.php), so the real OpenStreetMap is not called;
 *      - the backend, using the fake Nominatim;
 *      - the backend again, with Nominatim "down" (a closed port), to test the failure handling;
 *      - the backend with a database that does not exist, to test the 500 error response.
 * 3. Sends real HTTP requests to every endpoint and checks status codes and JSON.
 *
 * Needs a running MySQL server and backend/config/config.php.
 */

require_once __DIR__ . '/../../backend/config/Database.php';

const TEST_DB = 'aframe_living_test';
const FAKE_NOMINATIM = 'http://127.0.0.1:8093';
const API = 'http://127.0.0.1:8091';
const API_NOMINATIM_DOWN = 'http://127.0.0.1:8092';
const API_DATABASE_MISSING = 'http://127.0.0.1:8094';

$root = realpath(__DIR__ . '/../..');
$logDir = __DIR__ . '/logs';

// ---------------------------------------------------------------------
// Test helpers (same output style as the other tests)
// ---------------------------------------------------------------------

$results = ['passed' => 0, 'failed' => 0];

function check(string $name, bool $ok, string $detail = ''): void
{
    global $results;
    $results[$ok ? 'passed' : 'failed']++;
    echo ($ok ? 'PASS' : 'FAIL') . "  {$name}" . ($detail !== '' && !$ok ? "  ({$detail})" : '') . PHP_EOL;
}

/** Sends an HTTP request. Returns [status code, decoded JSON body (or raw text)]. */
function http(string $method, string $path, mixed $body = null, string $base = API): array
{
    $curl = curl_init($base . $path);
    $headers = ['Accept: application/json'];
    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($curl, CURLOPT_POSTFIELDS, is_string($body) ? $body : json_encode($body));
    }
    curl_setopt_array($curl, [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $raw = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);

    $json = json_decode((string) $raw, true);
    return [$status, $json ?? $raw];
}

/** Checks the status code (and returns the body for further checks). */
function expect(string $name, int $expectedStatus, string $method, string $path, mixed $body = null, string $base = API): mixed
{
    [$status, $json] = http($method, $path, $body, $base);
    $detail = "got {$status}: " . substr(is_string($json) ? $json : json_encode($json, JSON_UNESCAPED_UNICODE), 0, 160);
    check("{$name}  [{$method} {$path} -> {$expectedStatus}]", $status === $expectedStatus, $detail);
    return $json;
}

function startServer(string $address, string $docRoot, string $router, array $env = [], array $phpOptions = []): mixed
{
    $command = [PHP_BINARY, ...$phpOptions, '-S', $address, '-t', $docRoot, $router];
    $process = proc_open($command, [1 => ['file', 'NUL', 'w'], 2 => ['file', 'NUL', 'w']], $pipes, null, array_merge(getenv(), $env));

    // Wait until the server accepts connections (max. 5 seconds).
    [$host, $port] = explode(':', $address);
    for ($i = 0; $i < 50; $i++) {
        $socket = @fsockopen($host, (int) $port, $errno, $errstr, 0.1);
        if ($socket) {
            fclose($socket);
            return $process;
        }
        usleep(100_000);
    }
    exit("Could not start the server at {$address}.\n");
}

// ---------------------------------------------------------------------
// 1. Fresh test database
// ---------------------------------------------------------------------

$settings = Database::settings();
if ($settings['name'] === TEST_DB) {
    exit("config.php must point to the real database, not the test database.\n");
}
$server = new PDO(
    sprintf('mysql:host=%s;port=%d;charset=%s', $settings['host'], $settings['port'], $settings['charset']),
    $settings['user'],
    $settings['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$server->exec('DROP DATABASE IF EXISTS `' . TEST_DB . '`');
$server->exec('CREATE DATABASE `' . TEST_DB . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$server->exec('USE `' . TEST_DB . '`');
$sql = file_get_contents($root . '/backend/database/aframe_living.sql');
$server->exec(preg_replace('/^(CREATE DATABASE|USE)\b.*$/mi', '', $sql));
echo "Test database " . TEST_DB . " created from backend/database/aframe_living.sql\n";

// Fresh test logs
foreach (glob($logDir . '/*.log') ?: [] as $file) {
    unlink($file);
}

// ---------------------------------------------------------------------
// 2. Start the servers
// ---------------------------------------------------------------------

$bootstrap = ['-d', 'auto_prepend_file=' . __DIR__ . '/test-bootstrap.php'];
$servers = [
    startServer('127.0.0.1:8093', __DIR__, __DIR__ . '/fake-nominatim.php'),
    startServer('127.0.0.1:8091', $root . '/backend', $root . '/backend/index.php', ['TEST_NOMINATIM_URL' => FAKE_NOMINATIM], $bootstrap),
    startServer('127.0.0.1:8092', $root . '/backend', $root . '/backend/index.php', ['TEST_NOMINATIM_URL' => 'http://127.0.0.1:9'], $bootstrap),
    startServer('127.0.0.1:8094', $root . '/backend', $root . '/backend/index.php', ['TEST_DB_NAME' => 'aframe_living_missing'], $bootstrap),
];
register_shutdown_function(function () use ($servers) {
    foreach ($servers as $process) {
        proc_terminate($process);
    }
});
echo "Servers started (backend " . API . ", fake Nominatim " . FAKE_NOMINATIM . ")\n\n";

$db = new PDO(
    sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $settings['host'], $settings['port'], TEST_DB, $settings['charset']),
    $settings['user'],
    $settings['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

// ---------------------------------------------------------------------
// 3. Tests
// ---------------------------------------------------------------------

echo "--- General ---\n";
$doc = expect('OpenAPI document', 200, 'GET', '/openapi.json');
$paths = array_keys($doc['paths'] ?? []);
foreach (['/houses', '/houses/{idOrSlug}', '/houses/{id}/prices/{packageId}', '/houses/{id}/features', '/houses/{modelId}/images', '/images/{id}',
          '/packages', '/features', '/locations', '/projects', '/inquiries', '/users', '/users/{id}/inquiries', '/faqs', '/geocode',
          '/locations/{id}/geocode', '/locations/geocode-missing'] as $path) {
    check("OpenAPI documents {$path}", in_array($path, $paths, true));
}
$operations = 0;
foreach ($doc['paths'] ?? [] as $item) {
    $operations += count(array_intersect(array_keys($item), ['get', 'post', 'put', 'patch', 'delete']));
}
check("OpenAPI documents every endpoint ({$operations} operations)", $operations >= 60, "{$operations}");
check('OpenAPI server is this backend', ($doc['servers'][0]['url'] ?? '') === API, json_encode($doc['servers'] ?? null));
[$status, $html] = http('GET', '/docs/');
check('Swagger page is served', $status === 200 && str_contains((string) $html, 'swagger-ui'), "status {$status}");
$err = expect('unknown endpoint', 404, 'GET', '/does-not-exist');
check('unknown endpoint answers with JSON', isset($err['error']));
$err = expect('invalid JSON body', 400, 'POST', '/packages', '{"code": ');
check('invalid JSON is explained', isset($err['details']['body']));
expect('JSON array instead of object', 400, 'POST', '/packages', '[1, 2]');

echo "\n--- House models ---\n";
$houses = expect('list active models', 200, 'GET', '/houses');
check('4 models, smallest first', array_column($houses, 'slug') === ['atlantic', 'norway', 'scandinavia', 'continental'], json_encode(array_column($houses, 'slug')));
check('each model has 2 prices and a main photo', count($houses[1]['prices'] ?? []) === 2 && ($houses[1]['main_image']['sort_order'] ?? null) === 0);
check('Norway prices are 22000 / 48000', array_column($houses[1]['prices'], 'price_eur') === ['22000.00', '48000.00'], json_encode($houses[1]['prices']));
$norway = expect('model by slug', 200, 'GET', '/houses/norway');
check('details: features, photos and 11 projects', count($norway['features']) > 0 && count($norway['images']) > 0 && count($norway['projects']) === 11, count($norway['projects']) . ' projects');
check('Unicode stays readable (Šipovo)', in_array('Šipovo', array_column($norway['projects'], 'city'), true));
expect('model by id', 200, 'GET', '/houses/2');
expect('unknown slug', 404, 'GET', '/houses/igloo');
expect('unknown id', 404, 'GET', '/houses/999');

$newModel = [
    'slug' => 'test-cabin', 'name' => 'Test Cabin', 'area_m2' => 30, 'bedrooms' => 1, 'bathrooms' => 1,
    'footprint_width_m' => 4, 'footprint_length_m' => 6, 'height_m' => 4.5, 'is_active' => false,
];
$created = expect('create a model', 201, 'POST', '/houses', $newModel);
$modelId = (int) ($created['id'] ?? 0);
check('decimal stored exactly', ($created['height_m'] ?? '') === '4.50' && $created['is_active'] === 0, json_encode($created));
expect('duplicate slug', 409, 'POST', '/houses', $newModel);
$bad = expect('invalid model', 400, 'POST', '/houses', ['slug' => 'Bad Slug!', 'name' => '', 'area_m2' => 'big', 'bedrooms' => -1, 'colour' => 'red']);
check('every invalid field is reported', isset($bad['details']['slug'], $bad['details']['name'], $bad['details']['area_m2'], $bad['details']['bedrooms'], $bad['details']['colour'], $bad['details']['height_m']), json_encode($bad['details'] ?? null));
check('inactive model hidden from the public list', count(http('GET', '/houses')[1]) === 4);
check('inactive model shown with include_inactive', count(http('GET', '/houses?include_inactive=true')[1]) === 5);
expect('PUT needs all required fields', 400, 'PUT', "/houses/{$modelId}", ['name' => 'Only name']);
$updated = expect('PUT a model', 200, 'PUT', "/houses/{$modelId}", ['name' => 'Test Cabin 2'] + $newModel);
check('PUT changed the name', ($updated['name'] ?? '') === 'Test Cabin 2');
$patched = expect('PATCH a model', 200, 'PATCH', "/houses/{$modelId}", ['is_active' => true]);
check('PATCH changed only is_active', ($patched['is_active'] ?? null) === 1 && $patched['name'] === 'Test Cabin 2');
expect('PATCH with empty body', 400, 'PATCH', "/houses/{$modelId}", []);

$price = expect('set a price', 200, 'PUT', "/houses/{$modelId}/prices/1", ['price_eur' => 12500]);
check('price saved', ($price['price_eur'] ?? '') === '12500.00');
expect('change the price', 200, 'PUT', "/houses/{$modelId}/prices/1", ['price_eur' => 13000.5]);
expect('negative price', 400, 'PUT', "/houses/{$modelId}/prices/1", ['price_eur' => -5]);
expect('price in unknown package', 404, 'PUT', "/houses/{$modelId}/prices/99", ['price_eur' => 100]);
check('model has 1 price', count(http('GET', "/houses/{$modelId}/prices")[1]) === 1);
expect('delete the price', 200, 'DELETE', "/houses/{$modelId}/prices/1");
expect('delete it again', 404, 'DELETE', "/houses/{$modelId}/prices/1");

expect('add a feature', 201, 'POST', "/houses/{$modelId}/features", ['feature_id' => 1]);
expect('add the same feature again', 409, 'POST', "/houses/{$modelId}/features", ['feature_id' => 1]);
expect('add an unknown feature', 400, 'POST', "/houses/{$modelId}/features", ['feature_id' => 999]);
check('model has 1 feature', count(http('GET', "/houses/{$modelId}/features")[1]) === 1);
expect('remove the feature', 200, 'DELETE', "/houses/{$modelId}/features/1");
expect('remove it again', 404, 'DELETE', "/houses/{$modelId}/features/1");

echo "\n--- Photos ---\n";
$photos = expect('photos of Norway', 200, 'GET', '/houses/2/images');
check('main photo first', ($photos[0]['sort_order'] ?? null) === 0);
$photo = expect('add a photo', 201, 'POST', "/houses/{$modelId}/images", ['file_path' => 'assets/images/norway/norway1.JPG', 'alt_text' => 'Test photo', 'sort_order' => 0]);
$photoId = (int) ($photo['id'] ?? 0);
check('photo belongs to the model from the URL', (int) ($photo['model_id'] ?? 0) === $modelId);
expect('path outside assets/images', 400, 'POST', "/houses/{$modelId}/images", ['file_path' => 'assets/images/../../backend/config/config.php', 'alt_text' => 'x']);
expect('photo for unknown model', 404, 'POST', '/houses/999/images', ['file_path' => 'assets/images/a.jpg', 'alt_text' => 'x']);
expect('get the photo', 200, 'GET', "/images/{$photoId}");
expect('PUT the photo', 200, 'PUT', "/images/{$photoId}", ['file_path' => 'assets/images/norway/norway2.JPG', 'alt_text' => 'Changed']);
expect('PATCH the photo', 200, 'PATCH', "/images/{$photoId}", ['sort_order' => 3]);
expect('delete the photo', 200, 'DELETE', "/images/{$photoId}");
expect('photo is gone', 404, 'GET', "/images/{$photoId}");

echo "\n--- Packages ---\n";
check('2 packages', count(expect('list packages', 200, 'GET', '/packages')) === 2);
expect('get a package', 200, 'GET', '/packages/1');
expect('unknown package', 404, 'GET', '/packages/99');
$package = expect('create a package', 201, 'POST', '/packages', ['code' => 'shell_only', 'name' => 'Shell only']);
$packageId = (int) ($package['id'] ?? 0);
expect('duplicate code', 409, 'POST', '/packages', ['code' => 'roh_bau', 'name' => 'Copy']);
expect('invalid code', 400, 'POST', '/packages', ['code' => 'Shell Only', 'name' => 'x']);
expect('PUT a package', 200, 'PUT', "/packages/{$packageId}", ['code' => 'shell', 'name' => 'Shell', 'description' => 'Test']);
expect('PATCH to an existing code', 409, 'PATCH', "/packages/{$packageId}", ['code' => 'turnkey']);
expect('package with prices cannot be deleted', 409, 'DELETE', '/packages/1');
expect('delete an unused package', 200, 'DELETE', "/packages/{$packageId}");

echo "\n--- Features ---\n";
check('feature list', count(expect('list features', 200, 'GET', '/features')) >= 6);
check('filter by category', array_unique(array_column(expect('rooms only', 200, 'GET', '/features?category=room'), 'category')) === ['room']);
expect('unknown category', 400, 'GET', '/features?category=garden');
$feature = expect('feature with its models', 200, 'GET', '/features/1');
check('feature lists its models', isset($feature['models']) && count($feature['models']) > 0);
$newFeature = expect('create a feature', 201, 'POST', '/features', ['name' => 'Sauna', 'category' => 'equipment']);
$featureId = (int) ($newFeature['id'] ?? 0);
expect('duplicate name', 409, 'POST', '/features', ['name' => 'Sauna', 'category' => 'room']);
expect('invalid category', 400, 'POST', '/features', ['name' => 'Pool', 'category' => 'garden']);
expect('PUT a feature', 200, 'PUT', "/features/{$featureId}", ['name' => 'Finnish sauna', 'category' => 'equipment']);
expect('PATCH a feature', 200, 'PATCH', "/features/{$featureId}", ['category' => 'room']);
expect('delete a feature', 200, 'DELETE', "/features/{$featureId}");
expect('deleted feature is gone', 404, 'GET', "/features/{$featureId}");

echo "\n--- Projects ---\n";
$projects = expect('list projects', 200, 'GET', '/projects');
check('14 projects with model and city', count($projects) === 14 && isset($projects[0]['model_name'], $projects[0]['city']));
expect('get a project', 200, 'GET', '/projects/1');
$project = expect('create a project', 201, 'POST', '/projects', ['model_id' => $modelId, 'location_id' => 1, 'setting' => 'Lake', 'completed_on' => '2025-06-30']);
$projectId = (int) ($project['id'] ?? 0);
$bad = expect('unknown model and location', 400, 'POST', '/projects', ['model_id' => 999, 'location_id' => 1]);
check('unknown model is reported', isset($bad['details']['model_id']));
expect('completion date in the future', 400, 'POST', '/projects', ['model_id' => 1, 'location_id' => 1, 'completed_on' => '2099-01-01']);
expect('invalid date', 400, 'POST', '/projects', ['model_id' => 1, 'location_id' => 1, 'completed_on' => '2025-02-30']);
expect('PUT a project', 200, 'PUT', "/projects/{$projectId}", ['model_id' => $modelId, 'location_id' => 2]);
expect('PATCH a project', 200, 'PATCH', "/projects/{$projectId}", ['setting' => 'Forest']);
expect('model with projects cannot be deleted', 409, 'DELETE', "/houses/{$modelId}");
expect('location with projects cannot be deleted', 409, 'DELETE', '/locations/2');
expect('delete the project', 200, 'DELETE', "/projects/{$projectId}");
expect('model without projects can be deleted', 200, 'DELETE', "/houses/{$modelId}");
expect('deleted model is gone', 404, 'GET', "/houses/{$modelId}");

echo "\n--- Inquiries ---\n";
check('5 inquiries', count(expect('list inquiries', 200, 'GET', '/inquiries')) === 5);
check('filter by status', array_unique(array_column(expect('new only', 200, 'GET', '/inquiries?status=new'), 'status')) === ['new']);
expect('unknown status filter', 400, 'GET', '/inquiries?status=deleted');
$inquiry = expect('send an inquiry as a guest', 201, 'POST', '/inquiries', [
    'model_id' => 3, 'name' => 'Test Guest', 'email' => 'Guest@Example.com', 'subject' => 'Scandinavia question', 'message' => 'Is a basement possible?',
]);
$inquiryId = (int) ($inquiry['id'] ?? 0);
check('new inquiry has status "new" and lowercase e-mail', ($inquiry['status'] ?? '') === 'new' && $inquiry['email'] === 'guest@example.com');
expect('status cannot be chosen when sending', 400, 'POST', '/inquiries', ['name' => 'X Y', 'email' => 'x@y.com', 'subject' => 'Hello', 'message' => 'Hello there!', 'status' => 'closed']);
$bad = expect('invalid inquiry', 400, 'POST', '/inquiries', ['name' => 'A', 'email' => 'not-an-email', 'subject' => 'Hi', 'message' => 'short']);
check('all four fields reported', count($bad['details'] ?? []) === 4, json_encode($bad['details'] ?? null));
expect('unknown user', 400, 'POST', '/inquiries', ['user_id' => 999, 'name' => 'X Y', 'email' => 'x@y.com', 'subject' => 'Hello', 'message' => 'Hello there!']);
$changed = expect('admin changes the status', 200, 'PATCH', "/inquiries/{$inquiryId}", ['status' => 'answered']);
check('status changed', ($changed['status'] ?? '') === 'answered');
expect('invalid status', 400, 'PATCH', "/inquiries/{$inquiryId}", ['status' => 'deleted']);
expect('PUT an inquiry', 200, 'PUT', "/inquiries/{$inquiryId}", ['name' => 'Test Guest', 'email' => 'guest@example.com', 'subject' => 'Changed subject', 'message' => 'Changed message text', 'status' => 'closed']);
expect('get the inquiry', 200, 'GET', "/inquiries/{$inquiryId}");
expect('delete the inquiry', 200, 'DELETE', "/inquiries/{$inquiryId}");
expect('deleted inquiry is gone', 404, 'GET', "/inquiries/{$inquiryId}");

echo "\n--- Users ---\n";
$users = expect('list users', 200, 'GET', '/users');
check('4 users, no password hashes', count($users) === 4 && !array_key_exists('password_hash', $users[0]));
check('user inquiries (Ana has 2)', count(expect('inquiries of a user', 200, 'GET', '/users/2/inquiries')) === 2);
expect('inquiries of unknown user', 404, 'GET', '/users/999/inquiries');
$user = expect('create a user', 201, 'POST', '/users', ['first_name' => 'Test', 'last_name' => 'User', 'email' => 'test.user@example.com', 'password' => 'Secret123']);
$userId = (int) ($user['id'] ?? 0);
check('response has no password', !array_key_exists('password', $user) && !array_key_exists('password_hash', $user) && $user['role'] === 'customer');
$hash = $db->query("SELECT password_hash FROM users WHERE id = {$userId}")->fetchColumn();
check('password stored as a hash', $hash !== 'Secret123' && password_verify('Secret123', (string) $hash));
expect('duplicate e-mail', 409, 'POST', '/users', ['first_name' => 'A', 'last_name' => 'B', 'email' => 'ana.kovac@example.com', 'password' => 'Secret123']);
$bad = expect('weak password', 400, 'POST', '/users', ['first_name' => 'A', 'last_name' => 'B', 'email' => 'weak@example.com', 'password' => 'short']);
check('password problem reported', isset($bad['details']['password']));
expect('PUT without a password keeps the old one', 200, 'PUT', "/users/{$userId}", ['first_name' => 'Tester', 'last_name' => 'User', 'email' => 'test.user@example.com']);
check('old password still works', password_verify('Secret123', (string) $db->query("SELECT password_hash FROM users WHERE id = {$userId}")->fetchColumn()));
expect('PATCH a new password', 200, 'PATCH', "/users/{$userId}", ['password' => 'NewSecret456']);
check('new password works', password_verify('NewSecret456', (string) $db->query("SELECT password_hash FROM users WHERE id = {$userId}")->fetchColumn()));
expect('invalid role', 400, 'PATCH', "/users/{$userId}", ['role' => 'superuser']);
expect('last admin cannot become a customer', 409, 'PATCH', '/users/1', ['role' => 'customer']);
expect('last admin cannot be deleted', 409, 'DELETE', '/users/1');
expect('delete a customer', 200, 'DELETE', "/users/{$userId}");
expect('deleted user is gone', 404, 'GET', "/users/{$userId}");
expect('delete a user with inquiries', 200, 'DELETE', '/users/4');
check('their inquiry is kept without user', $db->query('SELECT user_id FROM inquiries WHERE id = 5')->fetchColumn() === null);

echo "\n--- FAQ ---\n";
$faqs = expect('list FAQ', 200, 'GET', '/faqs');
check('16 FAQ entries in page order', count($faqs) === 16 && $faqs[0]['category'] === 'models' && end($faqs)['category'] === 'process');
check('filter by category', count(expect('FAQ of one category', 200, 'GET', '/faqs?category=packages')) === 3);
expect('unknown category', 400, 'GET', '/faqs?category=prices');
$faq = expect('create an FAQ entry', 201, 'POST', '/faqs', ['category' => 'process', 'question' => 'Do you offer financing?', 'answer' => 'Please ask us about the options.', 'sort_order' => 5]);
$faqId = (int) ($faq['id'] ?? 0);
expect('link without label', 400, 'POST', '/faqs', ['category' => 'process', 'question' => 'Question?', 'answer' => 'Answer.', 'link_url' => '#/contact']);
expect('javascript: link is rejected', 400, 'POST', '/faqs', ['category' => 'process', 'question' => 'Question?', 'answer' => 'Answer.', 'link_label' => 'Click', 'link_url' => 'javascript:alert(1)']);
expect('PUT an FAQ entry', 200, 'PUT', "/faqs/{$faqId}", ['category' => 'process', 'question' => 'Do you offer financing?', 'answer' => 'No, but we can recommend banks.', 'link_label' => 'Contact us', 'link_url' => '#/contact']);
expect('PATCH removes only the label', 400, 'PATCH', "/faqs/{$faqId}", ['link_label' => null]);
expect('PATCH an FAQ entry', 200, 'PATCH', "/faqs/{$faqId}", ['sort_order' => 9]);
expect('delete the FAQ entry', 200, 'DELETE', "/faqs/{$faqId}");
expect('deleted entry is gone', 404, 'GET', "/faqs/{$faqId}");

echo "\n--- Locations and the external API (fake Nominatim) ---\n";
check('14 locations', count(expect('list locations', 200, 'GET', '/locations')) === 14);
expect('get a location', 200, 'GET', '/locations/5');
$place = expect('look up a place', 200, 'GET', '/geocode?city=Kupres&country=Bosnia%20and%20Herzegovina');
check('coordinates returned as numbers', ($place['latitude'] ?? null) === 43.990123 && ($place['longitude'] ?? null) === 17.271234, json_encode($place));
check('User-Agent identifies the app (Nominatim policy)', str_contains($place['display_name'] ?? '', 'UA=AFrameLiving'), $place['display_name'] ?? '');
expect('unknown place', 404, 'GET', '/geocode?city=Nowhere&country=Atlantis');
$bad = expect('city and country are required', 400, 'GET', '/geocode?city=Kupres');
check('missing country reported', isset($bad['details']['country']));
$broken = expect('Nominatim answers with an error', 503, 'GET', '/geocode?city=Brokenville&country=X');
check('friendly message', str_contains($broken['error'] ?? '', 'not available right now'));

$location = expect('create a location (coordinates looked up)', 201, 'POST', '/locations', ['city' => 'Mostar', 'region' => 'Herzegovina', 'country' => 'Bosnia and Herzegovina']);
$locationId = (int) ($location['id'] ?? 0);
check('coordinates saved', ($location['latitude'] ?? null) === '43.990123' && ($location['geocoding']['status'] ?? '') === 'found', json_encode($location));
expect('duplicate location', 409, 'POST', '/locations', ['city' => 'Mostar', 'country' => 'Bosnia and Herzegovina']);
$manual = expect('create with manual coordinates', 201, 'POST', '/locations', ['city' => 'Trebinje', 'country' => 'Bosnia and Herzegovina', 'latitude' => 42.71, 'longitude' => 18.34]);
check('manual coordinates kept, Nominatim skipped', ($manual['latitude'] ?? '') === '42.710000' && ($manual['geocoding']['status'] ?? '') === 'skipped');
expect('only latitude', 400, 'POST', '/locations', ['city' => 'Tuzla', 'country' => 'Bosnia and Herzegovina', 'latitude' => 44.5]);
expect('latitude out of range', 400, 'POST', '/locations', ['city' => 'Tuzla', 'country' => 'Bosnia and Herzegovina', 'latitude' => 120, 'longitude' => 18]);
$unknown = expect('place Nominatim does not know', 201, 'POST', '/locations', ['city' => 'Nowhere', 'country' => 'Bosnia and Herzegovina']);
check('saved without coordinates (not_found)', array_key_exists('latitude', $unknown ?? []) && $unknown['latitude'] === null && ($unknown['geocoding']['status'] ?? '') === 'not_found');
$renamed = expect('rename: coordinates looked up again', 200, 'PATCH', '/locations/' . ($unknown['id'] ?? 0), ['city' => 'Neum']);
check('renamed location now has coordinates', ($renamed['latitude'] ?? null) === '43.990123' && $renamed['geocoding']['status'] === 'found');
$regionOnly = expect('changing only the region keeps the coordinates', 200, 'PATCH', "/locations/{$locationId}", ['region' => 'Herzegovina-Neretva']);
check('coordinates unchanged', ($regionOnly['latitude'] ?? null) === '43.990123' && $regionOnly['geocoding']['status'] === 'skipped');
expect('PUT a location', 200, 'PUT', "/locations/{$locationId}", ['city' => 'Mostar', 'region' => 'Herzegovina', 'country' => 'Bosnia and Herzegovina']);
$again = expect('geocode one location', 200, 'POST', '/locations/5/geocode');
check('sample location Kupres now has coordinates', ($again['latitude'] ?? null) === '43.990123');
// Give all but two sample locations coordinates, so geocode-missing only needs 2 requests (1 per second).
for ($id = 1; $id <= 12; $id++) {
    http('PATCH', "/locations/{$id}", ['latitude' => 44, 'longitude' => 17]);
}
$summary = expect('geocode all missing locations', 200, 'POST', '/locations/geocode-missing');
check('2 found, 0 failed', ($summary['found'] ?? 0) === 2 && ($summary['failed'] ?? 1) === 0, json_encode(array_diff_key($summary ?? [], ['locations' => 1])));
expect('delete a location without projects', 200, 'DELETE', "/locations/{$locationId}");
check('projects show the coordinates for the map', http('GET', '/projects')[1][4]['latitude'] !== null);

echo "\n--- External API unavailable (Nominatim down) ---\n";
$down = expect('look-up while Nominatim is down', 503, 'GET', '/geocode?city=Kupres&country=Bosnia%20and%20Herzegovina', null, API_NOMINATIM_DOWN);
check('friendly message, no technical details', ($down['error'] ?? '') === 'The map service (OpenStreetMap) is not available right now. Please try again later.');
$saved = expect('location is still saved while Nominatim is down', 201, 'POST', '/locations', ['city' => 'Zenica', 'country' => 'Bosnia and Herzegovina'], API_NOMINATIM_DOWN);
check('saved without coordinates (failed)', array_key_exists('latitude', $saved ?? []) && $saved['latitude'] === null && ($saved['geocoding']['status'] ?? '') === 'failed', json_encode($saved));
expect('the rest of the API keeps working', 200, 'GET', '/houses', null, API_NOMINATIM_DOWN);

echo "\n--- Unexpected server error (database missing) ---\n";
$crash = expect('database error becomes a 500', 500, 'GET', '/houses', null, API_DATABASE_MISSING);
check('500 message has no SQL, paths or stack trace', ($crash['error'] ?? '') === 'Something went wrong on the server. Please try again later.' && count($crash) === 1, json_encode($crash));

echo "\n--- Logs ---\n";
$errorLog = (string) @file_get_contents($logDir . '/error.log');
$infoLog = (string) @file_get_contents($logDir . '/info.log');
check('error.log: Nominatim failure (connection)', str_contains($errorLog, 'ERROR Nominatim request failed'), $errorLog);
check('error.log: Nominatim HTTP 500', str_contains($errorLog, 'ERROR Nominatim returned HTTP 500'));
check('info.log: successful Nominatim call', (bool) preg_match('/^\[\d{4}-\d\d-\d\d \d\d:\d\d:\d\d\] INFO Nominatim GET \/search 200 found/m', $infoLog));
check('error.log: the 500 with the PDOException', (bool) preg_match('/ERROR GET \/houses 500 PDOException: .+ \(Database\.php:\d+\)/', $errorLog), $errorLog);
check('logs contain no stack traces', !str_contains($errorLog, '#0 '));

// ---------------------------------------------------------------------

$total = $results['passed'] + $results['failed'];
echo "\n{$results['passed']}/{$total} checks passed\n";
exit($results['failed'] === 0 ? 0 : 1);
