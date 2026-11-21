<?php
/*
 * BaseRoutes – small helpers shared by all route classes.
 *
 * A route class connects URLs to service methods:
 *
 *   Flight::route('GET /faqs/@id:[0-9]+', [FaqRoutes::class, 'getOne']);
 *
 * Each route method reads the request, calls ONE service method and sends JSON.
 * There is no SQL and no business logic in the routes. Errors are not handled
 * here: services throw an HttpException, and the error handler in index.php
 * turns it into a JSON error response with the right status code.
 *
 * The #[OA\...] lines above each method are the OpenAPI (Swagger) documentation
 * of that endpoint. They are read by GET /openapi.json, not run as code.
 */

abstract class BaseRoutes
{
    /** Sends $data as JSON with the given HTTP status code. */
    protected static function send(mixed $data, int $status = 200): void
    {
        // JSON_UNESCAPED_UNICODE keeps "Šipovo" readable instead of "Šipovo".
        Flight::json($data, $status, true, 'utf-8', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /** Sends a short confirmation, e.g. after DELETE. */
    protected static function message(string $message, int $status = 200): void
    {
        self::send(['message' => $message], $status);
    }

    /**
     * The JSON body of the request as an array.
     * Throws a 400 error if the body is not a JSON object.
     */
    protected static function body(): array
    {
        $raw = trim(Flight::request()->getBody());
        if ($raw === '') {
            return [];
        }
        $data = json_decode($raw, true);
        if (!is_array($data) || array_is_list($data) && $data !== []) {
            throw new ValidationException(['body' => 'Must be a JSON object, e.g. {"name": "..."}.']);
        }
        return $data;
    }

    /** One value from the query string (?status=new), or null. */
    protected static function query(string $name): ?string
    {
        $value = Flight::request()->query[$name] ?? null;
        return is_string($value) ? trim($value) : null;
    }
}
