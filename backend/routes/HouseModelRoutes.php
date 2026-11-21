<?php
/*
 * House model endpoints:
 *   /houses                                  the models
 *   /houses/{id}/prices/{packageId}          price of a model in a package
 *   /houses/{id}/features/{featureId}        features of a model
 * (photos of a model are in ModelImageRoutes)
 */

require_once __DIR__ . '/BaseRoutes.php';

use OpenApi\Attributes as OA;

class HouseModelRoutes extends BaseRoutes
{
    public static function register(): void
    {
        Flight::route('GET /houses', [self::class, 'getAll']);
        Flight::route('GET /houses/@key', [self::class, 'getOne']);
        Flight::route('POST /houses', [self::class, 'create']);
        Flight::route('PUT /houses/@id:[0-9]+', [self::class, 'replace']);
        Flight::route('PATCH /houses/@id:[0-9]+', [self::class, 'patch']);
        Flight::route('DELETE /houses/@id:[0-9]+', [self::class, 'delete']);

        Flight::route('GET /houses/@id:[0-9]+/prices', [self::class, 'getPrices']);
        Flight::route('PUT /houses/@id:[0-9]+/prices/@packageId:[0-9]+', [self::class, 'setPrice']);
        Flight::route('DELETE /houses/@id:[0-9]+/prices/@packageId:[0-9]+', [self::class, 'deletePrice']);

        Flight::route('GET /houses/@id:[0-9]+/features', [self::class, 'getFeatures']);
        Flight::route('POST /houses/@id:[0-9]+/features', [self::class, 'addFeature']);
        Flight::route('DELETE /houses/@id:[0-9]+/features/@featureId:[0-9]+', [self::class, 'removeFeature']);
    }

    private static function service(): HouseModelService
    {
        return Flight::houseModelService();
    }

    // ---------------------------------------------------------------
    // Models
    // ---------------------------------------------------------------

    #[OA\Get(path: '/houses', tags: ['House models'], summary: 'List the models (smallest first) with their prices and main photo',
        parameters: [new OA\Parameter(name: 'include_inactive', in: 'query', required: false, description: 'Also list hidden models (for the admin)', schema: new OA\Schema(type: 'boolean', default: false))],
        responses: [
            new OA\Response(response: 200, description: 'The models', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/HouseModelWithPrices'))),
        ])]
    public static function getAll(): void
    {
        $includeInactive = filter_var(self::query('include_inactive'), FILTER_VALIDATE_BOOLEAN);
        self::send(self::service()->getCatalogue($includeInactive));
    }

    #[OA\Get(path: '/houses/{idOrSlug}', tags: ['House models'], summary: 'Get one model with prices, features, photos and projects',
        parameters: [new OA\Parameter(name: 'idOrSlug', in: 'path', required: true, description: 'Id (e.g. 2) or slug (e.g. norway)', schema: new OA\Schema(type: 'string'), example: 'norway')],
        responses: [
            new OA\Response(response: 200, description: 'The model', content: new OA\JsonContent(ref: '#/components/schemas/HouseModelDetails')),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ])]
    public static function getOne(string $key): void
    {
        self::send(self::service()->getDetails($key));
    }

    #[OA\Post(path: '/houses', tags: ['House models'], summary: 'Add a model',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/HouseModel')),
        responses: [
            new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(ref: '#/components/schemas/HouseModel')),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
            new OA\Response(response: 409, ref: '#/components/responses/Conflict'),
        ])]
    public static function create(): void
    {
        self::send(self::service()->create(self::body()), 201);
    }

    #[OA\Put(path: '/houses/{id}', tags: ['House models'], summary: 'Replace a model (all required fields)',
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/HouseModel')),
        responses: [
            new OA\Response(response: 200, description: 'Updated', content: new OA\JsonContent(ref: '#/components/schemas/HouseModel')),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 409, ref: '#/components/responses/Conflict'),
        ])]
    public static function replace(string $id): void
    {
        self::send(self::service()->update((int) $id, self::body()));
    }

    #[OA\Patch(path: '/houses/{id}', tags: ['House models'], summary: 'Change some fields of a model, e.g. {"is_active": false}',
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/HouseModel')),
        responses: [
            new OA\Response(response: 200, description: 'Updated', content: new OA\JsonContent(ref: '#/components/schemas/HouseModel')),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 409, ref: '#/components/responses/Conflict'),
        ])]
    public static function patch(string $id): void
    {
        self::send(self::service()->update((int) $id, self::body(), true));
    }

    #[OA\Delete(path: '/houses/{id}', tags: ['House models'], summary: 'Delete a model (not possible while it has projects)',
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/Deleted'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 409, ref: '#/components/responses/Conflict'),
        ])]
    public static function delete(string $id): void
    {
        self::service()->delete((int) $id);
        self::message('House model deleted.');
    }

    // ---------------------------------------------------------------
    // Prices
    // ---------------------------------------------------------------

    #[OA\Get(path: '/houses/{id}/prices', tags: ['House models'], summary: 'Prices of a model in every package',
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'The prices', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/ModelPrice'))),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ])]
    public static function getPrices(string $id): void
    {
        self::send(self::service()->getPrices((int) $id));
    }

    #[OA\Put(path: '/houses/{id}/prices/{packageId}', tags: ['House models'], summary: 'Set the price of a model in a package (creates or changes it)',
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/id'),
            new OA\Parameter(name: 'packageId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['price_eur'], properties: [
            new OA\Property(property: 'price_eur', type: 'number', minimum: 0, example: 22000),
        ])),
        responses: [
            new OA\Response(response: 200, description: 'The saved price', content: new OA\JsonContent(ref: '#/components/schemas/ModelPrice')),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ])]
    public static function setPrice(string $id, string $packageId): void
    {
        self::send(self::service()->setPrice((int) $id, (int) $packageId, self::body()));
    }

    #[OA\Delete(path: '/houses/{id}/prices/{packageId}', tags: ['House models'], summary: 'Remove the price of a model in a package',
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/id'),
            new OA\Parameter(name: 'packageId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1),
        ],
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/Deleted'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ])]
    public static function deletePrice(string $id, string $packageId): void
    {
        self::service()->deletePrice((int) $id, (int) $packageId);
        self::message('Price deleted.');
    }

    // ---------------------------------------------------------------
    // Features
    // ---------------------------------------------------------------

    #[OA\Get(path: '/houses/{id}/features', tags: ['House models'], summary: 'Features of a model, rooms first',
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'The features', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Feature'))),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ])]
    public static function getFeatures(string $id): void
    {
        self::send(self::service()->getFeatures((int) $id));
    }

    #[OA\Post(path: '/houses/{id}/features', tags: ['House models'], summary: 'Give a model a feature',
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['feature_id'], properties: [
            new OA\Property(property: 'feature_id', type: 'integer', example: 4),
        ])),
        responses: [
            new OA\Response(response: 201, description: 'All features of the model', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Feature'))),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 409, ref: '#/components/responses/Conflict'),
        ])]
    public static function addFeature(string $id): void
    {
        self::send(self::service()->addFeature((int) $id, self::body()), 201);
    }

    #[OA\Delete(path: '/houses/{id}/features/{featureId}', tags: ['House models'], summary: 'Remove a feature from a model',
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/id'),
            new OA\Parameter(name: 'featureId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 4),
        ],
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/Deleted'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ])]
    public static function removeFeature(string $id, string $featureId): void
    {
        self::service()->removeFeature((int) $id, (int) $featureId);
        self::message('Feature removed from the model.');
    }
}
