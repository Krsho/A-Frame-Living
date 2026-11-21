<?php
/*
 * DAO tests (Milestone 2).
 *
 *   php tests/dao/run-dao-tests.php
 *
 * Builds a separate database `aframe_living_test` from backend/database/aframe_living.sql
 * and runs every DAO against it. The real `aframe_living` database is never touched.
 * Needs a running MySQL server and backend/config/config.php.
 */

require_once __DIR__ . '/../../backend/config/Database.php';
foreach (glob(__DIR__ . '/../../backend/dao/*Dao.php') as $file) {
    require_once $file;
}

const TEST_DB = 'aframe_living_test';

// ---------------------------------------------------------------------
// Tiny test helpers (same output style as the browser tests)
// ---------------------------------------------------------------------

$results = ['passed' => 0, 'failed' => 0];

function check(string $name, bool $ok, string $detail = ''): void
{
    global $results;
    $results[$ok ? 'passed' : 'failed']++;
    echo ($ok ? 'PASS' : 'FAIL') . "  {$name}" . ($detail !== '' ? "  ({$detail})" : '') . PHP_EOL;
}

/** Passes if $action throws an exception of class $class (and, optionally, a PDO error code). */
function checkThrows(string $name, string $class, callable $action, ?string $sqlState = null): void
{
    try {
        $action();
        check($name, false, 'no exception');
    } catch (Throwable $e) {
        $ok = $e instanceof $class && ($sqlState === null || (string) $e->getCode() === $sqlState);
        check($name, $ok, get_class($e) . ': ' . substr($e->getMessage(), 0, 90));
    }
}

// ---------------------------------------------------------------------
// 1. Fresh test database from the .sql file
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

// Run the whole .sql file, but without its own CREATE DATABASE / USE lines.
$sql = file_get_contents(__DIR__ . '/../../backend/database/aframe_living.sql');
$sql = preg_replace('/^(CREATE DATABASE|USE)\b.*$/mi', '', $sql);
$server->exec($sql);

Database::useSettings(['name' => TEST_DB]);
echo "Test database " . TEST_DB . " created from backend/database/aframe_living.sql\n\n";

$users = new UserDao();
$models = new HouseModelDao();
$packages = new PackageDao();
$prices = new ModelPriceDao();
$features = new FeatureDao();
$modelFeatures = new ModelFeatureDao();
$images = new ModelImageDao();
$locations = new LocationDao();
$projects = new ProjectDao();
$inquiries = new InquiryDao();

// ---------------------------------------------------------------------
// 2. Sample data
// ---------------------------------------------------------------------

$expectedCounts = [
    'users' => [$users, 4], 'house_models' => [$models, 4], 'packages' => [$packages, 2],
    'model_prices' => [$prices, 8], 'features' => [$features, 6], 'model_features' => [$modelFeatures, 16],
    'model_images' => [$images, 43], 'locations' => [$locations, 14], 'projects' => [$projects, 14],
    'inquiries' => [$inquiries, 5],
];
foreach ($expectedCounts as $table => [$dao, $expected]) {
    check("sample data: {$table} has {$expected} rows", $dao->count() === $expected, 'rows=' . $dao->count());
}
check('sample data: UTF-8 place names (Šipovo, Bihać, Brčko)',
    $locations->getByCity('Šipovo', 'Bosnia and Herzegovina') !== null
    && $locations->getByCity('Bihać', 'Bosnia and Herzegovina') !== null
    && $locations->getByCity('Brčko', 'Bosnia and Herzegovina') !== null);
$norway = $models->getBySlug('norway');
check('sample data: Norway prices are 22,000 / 48,000 EUR',
    array_column($prices->getForModel((int) $norway['id']), 'price_eur') === ['22000.00', '48000.00']);
check('sample data: 11 Norway projects', count($projects->getForModel((int) $norway['id'])) === 11);

// ---------------------------------------------------------------------
// 3. BaseDao behaviour
// ---------------------------------------------------------------------

check('BaseDao getAll: sorted by id by default', array_column($models->getAll(), 'slug') === ['atlantic', 'norway', 'scandinavia', 'continental']);
check('BaseDao getAll: custom order + limit/offset', array_column($models->getAll('area_m2', 'DESC', 2, 1), 'slug') === ['scandinavia', 'norway']);
check('BaseDao getById: missing id returns null', $models->getById(9999) === null);
check('BaseDao update: missing id returns null', $packages->update(9999, ['name' => 'X']) === null);
check('BaseDao delete: missing id returns false', $packages->delete(9999) === false);
checkThrows('BaseDao add: unknown column is rejected', InvalidArgumentException::class,
    fn () => $packages->add(['code' => 'x', 'name' => 'X', 'is_admin' => 1]));
checkThrows('BaseDao add: empty data is rejected', InvalidArgumentException::class, fn () => $packages->add([]));
checkThrows('BaseDao getAll: unknown sort column is rejected', InvalidArgumentException::class,
    fn () => $models->getAll('name; DROP TABLE users'));
checkThrows('BaseDao getAll: sorting by a column the table does not have is rejected', InvalidArgumentException::class,
    fn () => $packages->getAll('created_at'));
checkThrows('BaseDao getById on a composite-key table is not allowed', LogicException::class, fn () => $prices->getById(1));

// ---------------------------------------------------------------------
// 4. CRUD for every entity
// ---------------------------------------------------------------------

// users
$user = $users->add(['first_name' => 'Test', 'last_name' => 'User', 'email' => 'test.user@example.com',
    'password_hash' => password_hash('Secret123', PASSWORD_DEFAULT), 'role' => 'customer']);
check('users: create returns the new row', $user['email'] === 'test.user@example.com' && $user['role'] === 'customer');
check('users: password_hash is never returned by normal reads',
    !array_key_exists('password_hash', $user)
    && !array_key_exists('password_hash', $users->getById((int) $user['id']))
    && !array_key_exists('password_hash', $users->getAll()[0])
    && !array_key_exists('password_hash', $users->getByEmail('test.user@example.com')));
$auth = $users->getByEmailWithPassword('test.user@example.com');
check('users: login lookup includes a verifiable password hash', password_verify('Secret123', $auth['password_hash']));
check('users: update (PATCH one field)', $users->update((int) $user['id'], ['phone' => '+387 61 555 555'])['phone'] === '+387 61 555 555');
check('users: getByRole', count($users->getByRole('admin')) === 1);
checkThrows('users: duplicate email is rejected', PDOException::class, fn () => $users->add([
    'first_name' => 'A', 'last_name' => 'B', 'email' => 'test.user@example.com', 'password_hash' => 'x']), '23000');
checkThrows('users: invalid role is rejected (strict mode)', PDOException::class,
    fn () => $users->update((int) $user['id'], ['role' => 'superuser']));
check('users: delete', $users->delete((int) $user['id']) && $users->getById((int) $user['id']) === null);

// house_models
$model = $models->add(['slug' => 'test-model', 'name' => 'Test Model', 'summary' => 'Only for tests.',
    'area_m2' => 60, 'bedrooms' => 2, 'bathrooms' => 1, 'footprint_width_m' => 6, 'footprint_length_m' => 8,
    'height_m' => 6.2, 'is_active' => 0]);
$modelId = (int) $model['id'];
check('house_models: create', $model['slug'] === 'test-model' && $model['side_room_width_m'] === null);
check('house_models: getBySlug', $models->getBySlug('test-model')['name'] === 'Test Model');
check('house_models: getActive hides inactive models', !in_array('test-model', array_column($models->getActive(), 'slug'), true));
check('house_models: update (PUT several fields)',
    $models->update($modelId, ['name' => 'Test Model 2', 'area_m2' => 62.5, 'is_active' => 1])['area_m2'] === '62.50');
checkThrows('house_models: duplicate slug is rejected', PDOException::class, fn () => $models->add([
    'slug' => 'norway', 'name' => 'Copy', 'area_m2' => 1, 'bedrooms' => 1, 'bathrooms' => 1,
    'footprint_width_m' => 1, 'footprint_length_m' => 1, 'height_m' => 1]), '23000');

// packages
$package = $packages->add(['code' => 'test_pkg', 'name' => 'Test package', 'description' => 'For tests']);
check('packages: create + getByCode', $packages->getByCode('test_pkg')['id'] === $package['id']);
check('packages: update', $packages->update((int) $package['id'], ['name' => 'Renamed'])['name'] === 'Renamed');

// model_prices (composite key)
check('model_prices: create with setPrice', $prices->setPrice($modelId, (int) $package['id'], 55000)['price_eur'] === '55000.00');
check('model_prices: update with setPrice (same key)', $prices->setPrice($modelId, (int) $package['id'], 56000)['price_eur'] === '56000.00');
check('model_prices: still one row after the update', count($prices->getForModel($modelId)) === 1);
checkThrows('model_prices: negative price is rejected', PDOException::class,
    fn () => $prices->setPrice($modelId, (int) $package['id'], -1));
check('model_prices: deletePrice', $prices->deletePrice($modelId, (int) $package['id']) && $prices->getPrice($modelId, (int) $package['id']) === null);
checkThrows('model_prices: price for a non-existent model is rejected (foreign key)', PDOException::class,
    fn () => $prices->setPrice(9999, 1, 100), '23000');

// features + model_features (composite key)
$feature = $features->add(['name' => 'Sauna', 'category' => 'equipment']);
check('features: create', $feature['name'] === 'Sauna');
check('features: getByCategory', in_array('Sauna', array_column($features->getByCategory('equipment'), 'name'), true));
checkThrows('features: invalid category is rejected (strict mode)', PDOException::class,
    fn () => $features->add(['name' => 'Pool', 'category' => 'outdoor']));
check('model_features: attach', $modelFeatures->attach($modelId, (int) $feature['id']) === true);
check('model_features: attaching twice does nothing', $modelFeatures->attach($modelId, (int) $feature['id']) === false);
check('model_features: getFeaturesForModel', array_column($modelFeatures->getFeaturesForModel($modelId), 'name') === ['Sauna']);
$continental = $models->getBySlug('continental');
check('model_features: Continental has a fireplace, rooms listed first',
    array_column($modelFeatures->getFeaturesForModel((int) $continental['id']), 'category')[0] === 'room'
    && in_array('Fireplace', array_column($modelFeatures->getFeaturesForModel((int) $continental['id']), 'name'), true));
check('model_features: getModelsWithFeature', array_column($modelFeatures->getModelsWithFeature((int) $feature['id']), 'slug') === ['test-model']);
check('model_features: detach', $modelFeatures->detach($modelId, (int) $feature['id']) === true);
check('features: update', $features->update((int) $feature['id'], ['category' => 'construction'])['category'] === 'construction');

// model_images
$image2 = $images->add(['model_id' => $modelId, 'file_path' => 'assets/images/test/2.jpg', 'alt_text' => 'Second', 'sort_order' => 1]);
$image1 = $images->add(['model_id' => $modelId, 'file_path' => 'assets/images/test/1.jpg', 'alt_text' => 'Main', 'sort_order' => 0]);
check('model_images: getForModel returns the main photo first', array_column($images->getForModel($modelId), 'alt_text') === ['Main', 'Second']);
check('model_images: update', $images->update((int) $image2['id'], ['alt_text' => 'Second photo'])['alt_text'] === 'Second photo');
check('model_images: delete', $images->delete((int) $image2['id']) && count($images->getForModel($modelId)) === 1);

// locations
$location = $locations->add(['city' => 'Mostar', 'region' => 'Herzegovina', 'country' => 'Bosnia and Herzegovina']);
check('locations: create', $location['city'] === 'Mostar' && $location['latitude'] === null);
check('locations: listed as missing coordinates', in_array('Mostar', array_column($locations->getWithoutCoordinates(), 'city'), true));
$geocoded = $locations->updateCoordinates((int) $location['id'], 43.343033, 17.807894);
check('locations: updateCoordinates saves lat/lng and the time', $geocoded['latitude'] === '43.343033' && $geocoded['geocoded_at'] !== null);
checkThrows('locations: duplicate city + country is rejected', PDOException::class,
    fn () => $locations->add(['city' => 'Mostar', 'country' => 'Bosnia and Herzegovina']), '23000');

// projects
$project = $projects->add(['model_id' => $modelId, 'location_id' => (int) $location['id'], 'setting' => 'Town outskirts']);
check('projects: create', $project['setting'] === 'Town outskirts');
$details = array_values(array_filter($projects->getAllWithDetails(), fn ($p) => $p['id'] === $project['id']))[0];
check('projects: getAllWithDetails joins model and location', $details['model_name'] === 'Test Model 2' && $details['city'] === 'Mostar');
check('projects: update', $projects->update((int) $project['id'], ['completed_on' => '2026-06-30'])['completed_on'] === '2026-06-30');
checkThrows('projects: a model with projects cannot be deleted (RESTRICT)', PDOException::class,
    fn () => $models->delete($modelId), '23000');
checkThrows('projects: a location with projects cannot be deleted (RESTRICT)', PDOException::class,
    fn () => $locations->delete((int) $location['id']), '23000');
check('projects: delete', $projects->delete((int) $project['id']));
check('locations: delete once it has no projects', $locations->delete((int) $location['id']));

// inquiries
$guest = $inquiries->add(['model_id' => $modelId, 'name' => 'Guest', 'email' => 'guest@example.com',
    'subject' => "Robert'); DROP TABLE inquiries;--", 'message' => 'Testing prepared statements.']);
check('inquiries: create (guest, status defaults to new)', $guest['user_id'] === null && $guest['status'] === 'new');
check('inquiries: SQL in the input is stored as plain text', $inquiries->getById((int) $guest['id'])['subject'] === "Robert'); DROP TABLE inquiries;--" && $inquiries->count() === 6);
check('inquiries: getForUser (Ana has 2)', count($inquiries->getForUser(2)) === 2);
check('inquiries: getAllWithModel joins the model name', array_values(array_filter($inquiries->getAllWithModel(), fn ($i) => $i['id'] === $guest['id']))[0]['model_name'] === 'Test Model 2');
check('inquiries: updateStatus', $inquiries->updateStatus((int) $guest['id'], 'answered')['status'] === 'answered');
check('inquiries: getByStatus', in_array($guest['id'], array_column($inquiries->getByStatus('answered'), 'id'), true));
checkThrows('inquiries: invalid status is rejected (strict mode)', PDOException::class,
    fn () => $inquiries->updateStatus((int) $guest['id'], 'deleted'));

// ---------------------------------------------------------------------
// 5. Foreign-key delete rules
// ---------------------------------------------------------------------

$prices->setPrice($modelId, 1, 1000);
$modelFeatures->attach($modelId, 1);
check('house_models: delete works once it has no projects', $models->delete($modelId));
check('house_models delete: photos, prices and features are deleted with it (CASCADE)',
    $images->getForModel($modelId) === [] && $prices->getForModel($modelId) === [] && $modelFeatures->getFeaturesForModel($modelId) === []);
check('house_models delete: the inquiry is kept, its model link set to NULL', $inquiries->getById((int) $guest['id'])['model_id'] === null);

$customer = $users->add(['first_name' => 'Temp', 'last_name' => 'Customer', 'email' => 'temp@example.com', 'password_hash' => 'x']);
$own = $inquiries->add(['user_id' => (int) $customer['id'], 'name' => 'Temp', 'email' => 'temp@example.com', 'subject' => 'Hi', 'message' => 'Hello there']);
$users->delete((int) $customer['id']);
check('users delete: their inquiries are kept, user link set to NULL', $inquiries->getById((int) $own['id'])['user_id'] === null);
check('inquiries: delete', $inquiries->delete((int) $own['id']) && $inquiries->delete((int) $guest['id']));
check('packages: delete', $packages->delete((int) $package['id']));
check('features: delete', $features->delete((int) $feature['id']));

// ---------------------------------------------------------------------

$total = $results['passed'] + $results['failed'];
echo PHP_EOL . "{$results['passed']}/{$total} checks passed" . PHP_EOL;
exit($results['failed'] > 0 ? 1 : 0);
