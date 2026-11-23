<?php
/*
 * Location endpoints: /locations
 * and the external API endpoints (OpenStreetMap Nominatim):
 *   GET  /geocode?city=...&country=...     look up a place (nothing is saved)
 *   POST /locations/{id}/geocode           look up and save the coordinates of one location
 *   POST /locations/geocode-missing        the same for every location without coordinates
 */

require_once __DIR__ . '/BaseRoutes.php';

use OpenApi\Attributes as OA;

class LocationRoutes extends BaseRoutes
{
    public static function register(): void
    {
        Flight::route('GET /locations', [self::class, 'getAll']);
        Flight::route('GET /locations/@id:[0-9]+', [self::class, 'getOne']);
        Flight::route('POST /locations', [self::class, 'create']);
        Flight::route('PUT /locations/@id:[0-9]+', [self::class, 'replace']);
        Flight::route('PATCH /locations/@id:[0-9]+', [self::class, 'patch']);
        Flight::route('DELETE /locations/@id:[0-9]+', [self::class, 'delete']);

        Flight::route('GET /geocode', [self::class, 'lookup']);
        Flight::route('POST /locations/@id:[0-9]+/geocode', [self::class, 'geocode']);
        Flight::route('POST /locations/geocode-missing', [self::class, 'geocodeMissing']);
    }

    private static function service(): LocationService
    {
        return Flight::locationService();
    }

    #[OA\Get(path: '/locations', tags: ['Locations'], summary: 'List the locations (A–Z)',
        responses: [
            new OA\Response(response: 200, description: 'The locations', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Location'))),
        ])]
    public static function getAll(): void
    {
        self::send(self::service()->getAll());
    }

    #[OA\Get(path: '/locations/{id}', tags: ['Locations'], summary: 'Get one location',
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'The location', content: new OA\JsonContent(ref: '#/components/schemas/Location')),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ])]
    public static function getOne(string $id): void
    {
        self::send(self::service()->getById((int) $id));
    }

    #[OA\Post(path: '/locations', tags: ['Locations'], summary: 'Add a location; its coordinates are looked up with Nominatim',
        description: 'If Nominatim is unavailable, the location is still saved (201) without coordinates, and "geocoding" explains why.',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/Location')),
        responses: [
            new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(ref: '#/components/schemas/LocationWithGeocoding')),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
            new OA\Response(response: 409, ref: '#/components/responses/Conflict'),
        ])]
    public static function create(): void
    {
        self::send(self::service()->create(self::body()), 201);
    }

    #[OA\Put(path: '/locations/{id}', tags: ['Locations'], summary: 'Replace a location; if the place changed, the coordinates are looked up again',
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/Location')),
        responses: [
            new OA\Response(response: 200, description: 'Updated', content: new OA\JsonContent(ref: '#/components/schemas/LocationWithGeocoding')),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 409, ref: '#/components/responses/Conflict'),
        ])]
    public static function replace(string $id): void
    {
        self::send(self::service()->update((int) $id, self::body()));
    }

    #[OA\Patch(path: '/locations/{id}', tags: ['Locations'], summary: 'Change some fields of a location',
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/Location')),
        responses: [
            new OA\Response(response: 200, description: 'Updated', content: new OA\JsonContent(ref: '#/components/schemas/LocationWithGeocoding')),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 409, ref: '#/components/responses/Conflict'),
        ])]
    public static function patch(string $id): void
    {
        self::send(self::service()->update((int) $id, self::body(), true));
    }

    #[OA\Delete(path: '/locations/{id}', tags: ['Locations'], summary: 'Delete a location (not possible while it has projects)',
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/Deleted'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 409, ref: '#/components/responses/Conflict'),
        ])]
    public static function delete(string $id): void
    {
        self::service()->delete((int) $id);
        self::message('Location deleted.');
    }

    // ---------------------------------------------------------------
    // External API: OpenStreetMap Nominatim
    // ---------------------------------------------------------------

    #[OA\Get(path: '/geocode', tags: ['External API'], summary: 'Find the coordinates of a place with OpenStreetMap Nominatim (nothing is saved)',
        parameters: [
            new OA\Parameter(name: 'city', in: 'query', required: true, schema: new OA\Schema(type: 'string'), example: 'Kupres'),
            new OA\Parameter(name: 'country', in: 'query', required: true, schema: new OA\Schema(type: 'string'), example: 'Bosnia and Herzegovina'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'The place', content: new OA\JsonContent(ref: '#/components/schemas/Place')),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
            new OA\Response(response: 404, description: 'Nominatim does not know this place', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 503, ref: '#/components/responses/ServiceUnavailable'),
        ])]
    public static function lookup(): void
    {
        $query = array_filter(['city' => self::query('city'), 'country' => self::query('country')], fn ($v) => $v !== null);
        self::send(self::service()->lookup($query));
    }

    #[OA\Post(path: '/locations/{id}/geocode', tags: ['External API'], summary: 'Look up and save the coordinates of one location',
        description: 'Always 200 when the location exists; "geocoding.status" says whether coordinates were found (found, not_found or failed).',
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'The location and the result', content: new OA\JsonContent(ref: '#/components/schemas/LocationWithGeocoding')),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ])]
    public static function geocode(string $id): void
    {
        self::send(self::service()->geocodeById((int) $id));
    }

    #[OA\Post(path: '/locations/geocode-missing', tags: ['External API'], summary: 'Look up the coordinates of every location that has none (1 request per second)',
        responses: [
            new OA\Response(response: 200, description: 'How many were found', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'found', type: 'integer', example: 13),
                new OA\Property(property: 'not_found', type: 'integer', example: 1),
                new OA\Property(property: 'failed', type: 'integer', example: 0),
                new OA\Property(property: 'locations', type: 'array', items: new OA\Items(ref: '#/components/schemas/LocationWithGeocoding')),
            ])),
        ])]
    public static function geocodeMissing(): void
    {
        self::send(self::service()->geocodeMissing());
    }
}
