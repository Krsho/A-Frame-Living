<?php
/*
 * GET /openapi.json – the OpenAPI document of this API.
 *
 * swagger-php reads the #[OA\...] attributes in backend/routes/ and backend/docs/
 * and builds the document on every request, so the documentation always matches
 * the code. The Swagger page (backend/docs/index.html) displays it.
 */

require_once __DIR__ . '/BaseRoutes.php';

use OpenApi\Attributes as OA;
use OpenApi\Generator;

class DocsRoutes extends BaseRoutes
{
    public static function register(): void
    {
        Flight::route('GET /openapi.json', [self::class, 'openApi']);
        // Opening the backend root in a browser leads to the Swagger page.
        Flight::route('GET /', fn () => Flight::redirect(self::baseUrl() . '/docs/'));
    }

    public static function openApi(): void
    {
        // swagger-php reads the attributes with PHP reflection, so the classes that
        // carry them must be loaded (the route classes already are, see index.php).
        require_once __DIR__ . '/../docs/OpenApiDefinitions.php';

        $openApi = (new Generator())->generate([__DIR__, __DIR__ . '/../docs']);

        // The server address is where this backend runs right now
        // (e.g. http://localhost:8080), so "Try it out" works locally and when deployed.
        $openApi->servers = [new OA\Server(url: self::baseUrl(), description: 'This backend')];

        // toJson() already returns JSON text, so it is sent as it is (no second json_encode).
        Flight::json($openApi->toJson(), 200, false);
    }

    /** e.g. "http://localhost:8080" or "https://example.com/backend" */
    private static function baseUrl(): string
    {
        $request = Flight::request();
        $base = rtrim(str_replace('\\', '/', $request->base), '/');
        return $request->scheme . '://' . $request->host . $base;
    }
}
