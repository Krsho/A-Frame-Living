<?php
/*
 * index.php – the single entry point of the REST API.
 *
 * Every request goes through this file:
 *   - PHP's built-in server:  php -S localhost:8080 -t backend backend/index.php
 *   - Apache (XAMPP):         backend/.htaccess sends every request here
 *
 * It loads the classes, registers the services and routes, and starts FlightPHP.
 * Request flow:  HTTP request -> route -> service -> DAO -> MySQL -> JSON response
 * (middleware for JWT, roles and request logging is added in Milestone 4).
 */

// With PHP's built-in server, let it serve real files directly (e.g. docs/index.html).
if (PHP_SAPI === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $file = __DIR__ . $path;
    if ($path !== '/' && (is_file($file) || is_file(rtrim($file, '/') . '/index.html'))) {
        return false;
    }
}

require __DIR__ . '/vendor/autoload.php';

// Local time for log entries and dates (PHP's default is UTC).
date_default_timezone_set('Europe/Sarajevo');

// ---------------------------------------------------------------------
// Load our own classes (order: config, helpers, DAOs, services, routes)
// ---------------------------------------------------------------------
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/exceptions/HttpExceptions.php';
require_once __DIR__ . '/utils/Logger.php';
require_once __DIR__ . '/utils/ErrorHandler.php';
foreach (['dao', 'services', 'routes'] as $folder) {
    foreach (glob(__DIR__ . "/{$folder}/*.php") as $file) {
        require_once $file;
    }
}

// ---------------------------------------------------------------------
// Services: Flight::register() creates each one once, when it is first used,
// e.g. Flight::houseModelService()->getCatalogue(false)
// ---------------------------------------------------------------------
Flight::register('houseModelService', HouseModelService::class);
Flight::register('modelImageService', ModelImageService::class);
Flight::register('packageService', PackageService::class);
Flight::register('featureService', FeatureService::class);
Flight::register('locationService', LocationService::class);
Flight::register('projectService', ProjectService::class);
Flight::register('inquiryService', InquiryService::class);
Flight::register('userService', UserService::class);
Flight::register('faqService', FaqService::class);

// ---------------------------------------------------------------------
// Errors: always JSON, never a stack trace (details go to logs/error.log)
// ---------------------------------------------------------------------
Flight::map('error', [ErrorHandler::class, 'handle']);
Flight::map('notFound', function () {
    Flight::json(['error' => 'Endpoint not found.'], 404);
});
Flight::map('methodNotFound', function () {
    Flight::json(['error' => 'This HTTP method is not allowed for this endpoint.'], 405);
});

// ---------------------------------------------------------------------
// Routes
// ---------------------------------------------------------------------
HouseModelRoutes::register();
ModelImageRoutes::register();
PackageRoutes::register();
FeatureRoutes::register();
LocationRoutes::register();
ProjectRoutes::register();
InquiryRoutes::register();
UserRoutes::register();
FaqRoutes::register();
DocsRoutes::register();

Flight::start();
