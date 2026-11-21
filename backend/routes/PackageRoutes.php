<?php
/* Package endpoints: /packages */

require_once __DIR__ . '/BaseRoutes.php';

use OpenApi\Attributes as OA;

class PackageRoutes extends BaseRoutes
{
    public static function register(): void
    {
        Flight::route('GET /packages', [self::class, 'getAll']);
        Flight::route('GET /packages/@id:[0-9]+', [self::class, 'getOne']);
        Flight::route('POST /packages', [self::class, 'create']);
        Flight::route('PUT /packages/@id:[0-9]+', [self::class, 'replace']);
        Flight::route('PATCH /packages/@id:[0-9]+', [self::class, 'patch']);
        Flight::route('DELETE /packages/@id:[0-9]+', [self::class, 'delete']);
    }

    private static function service(): PackageService
    {
        return Flight::packageService();
    }

    #[OA\Get(path: '/packages', tags: ['Packages'], summary: 'List the packages',
        responses: [
            new OA\Response(response: 200, description: 'The packages', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Package'))),
        ])]
    public static function getAll(): void
    {
        self::send(self::service()->getAll());
    }

    #[OA\Get(path: '/packages/{id}', tags: ['Packages'], summary: 'Get one package',
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'The package', content: new OA\JsonContent(ref: '#/components/schemas/Package')),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ])]
    public static function getOne(string $id): void
    {
        self::send(self::service()->getById((int) $id));
    }

    #[OA\Post(path: '/packages', tags: ['Packages'], summary: 'Add a package',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/Package')),
        responses: [
            new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(ref: '#/components/schemas/Package')),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
            new OA\Response(response: 409, ref: '#/components/responses/Conflict'),
        ])]
    public static function create(): void
    {
        self::send(self::service()->create(self::body()), 201);
    }

    #[OA\Put(path: '/packages/{id}', tags: ['Packages'], summary: 'Replace a package (all required fields)',
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/Package')),
        responses: [
            new OA\Response(response: 200, description: 'Updated', content: new OA\JsonContent(ref: '#/components/schemas/Package')),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 409, ref: '#/components/responses/Conflict'),
        ])]
    public static function replace(string $id): void
    {
        self::send(self::service()->update((int) $id, self::body()));
    }

    #[OA\Patch(path: '/packages/{id}', tags: ['Packages'], summary: 'Change some fields of a package',
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/Package')),
        responses: [
            new OA\Response(response: 200, description: 'Updated', content: new OA\JsonContent(ref: '#/components/schemas/Package')),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 409, ref: '#/components/responses/Conflict'),
        ])]
    public static function patch(string $id): void
    {
        self::send(self::service()->update((int) $id, self::body(), true));
    }

    #[OA\Delete(path: '/packages/{id}', tags: ['Packages'], summary: 'Delete a package (not possible while models have a price in it)',
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/Deleted'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 409, ref: '#/components/responses/Conflict'),
        ])]
    public static function delete(string $id): void
    {
        self::service()->delete((int) $id);
        self::message('Package deleted.');
    }
}
