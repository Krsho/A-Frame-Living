<?php
/* Feature endpoints: /features */

require_once __DIR__ . '/BaseRoutes.php';

use OpenApi\Attributes as OA;

class FeatureRoutes extends BaseRoutes
{
    public static function register(): void
    {
        Flight::route('GET /features', [self::class, 'getAll']);
        Flight::route('GET /features/@id:[0-9]+', [self::class, 'getOne']);
        Flight::route('POST /features', [self::class, 'create']);
        Flight::route('PUT /features/@id:[0-9]+', [self::class, 'replace']);
        Flight::route('PATCH /features/@id:[0-9]+', [self::class, 'patch']);
        Flight::route('DELETE /features/@id:[0-9]+', [self::class, 'delete']);
    }

    private static function service(): FeatureService
    {
        return Flight::featureService();
    }

    #[OA\Get(path: '/features', tags: ['Features'], summary: 'List the features (A–Z)',
        parameters: [new OA\Parameter(name: 'category', in: 'query', required: false, description: 'Only this category', schema: new OA\Schema(type: 'string', enum: ['room', 'construction', 'equipment']))],
        responses: [
            new OA\Response(response: 200, description: 'The features', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Feature'))),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
        ])]
    public static function getAll(): void
    {
        self::send(self::service()->getAllFiltered(self::query('category')));
    }

    #[OA\Get(path: '/features/{id}', tags: ['Features'], summary: 'Get one feature and the models that have it',
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'The feature', content: new OA\JsonContent(allOf: [
                new OA\Schema(ref: '#/components/schemas/Feature'),
                new OA\Schema(properties: [new OA\Property(property: 'models', type: 'array', items: new OA\Items(properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 4),
                    new OA\Property(property: 'slug', type: 'string', example: 'continental'),
                    new OA\Property(property: 'name', type: 'string', example: 'Continental'),
                ]))]),
            ])),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ])]
    public static function getOne(string $id): void
    {
        self::send(self::service()->getWithModels((int) $id));
    }

    #[OA\Post(path: '/features', tags: ['Features'], summary: 'Add a feature',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/Feature')),
        responses: [
            new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(ref: '#/components/schemas/Feature')),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
            new OA\Response(response: 409, ref: '#/components/responses/Conflict'),
        ])]
    public static function create(): void
    {
        self::send(self::service()->create(self::body()), 201);
    }

    #[OA\Put(path: '/features/{id}', tags: ['Features'], summary: 'Replace a feature (all required fields)',
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/Feature')),
        responses: [
            new OA\Response(response: 200, description: 'Updated', content: new OA\JsonContent(ref: '#/components/schemas/Feature')),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 409, ref: '#/components/responses/Conflict'),
        ])]
    public static function replace(string $id): void
    {
        self::send(self::service()->update((int) $id, self::body()));
    }

    #[OA\Patch(path: '/features/{id}', tags: ['Features'], summary: 'Change some fields of a feature',
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/Feature')),
        responses: [
            new OA\Response(response: 200, description: 'Updated', content: new OA\JsonContent(ref: '#/components/schemas/Feature')),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 409, ref: '#/components/responses/Conflict'),
        ])]
    public static function patch(string $id): void
    {
        self::send(self::service()->update((int) $id, self::body(), true));
    }

    #[OA\Delete(path: '/features/{id}', tags: ['Features'], summary: 'Delete a feature (it is also removed from every model)',
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/Deleted'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ])]
    public static function delete(string $id): void
    {
        self::service()->delete((int) $id);
        self::message('Feature deleted.');
    }
}
