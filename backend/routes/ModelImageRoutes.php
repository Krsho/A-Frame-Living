<?php
/*
 * Photo endpoints:
 *   /houses/{id}/images   list and add photos of a model
 *   /images/{id}          one photo
 */

require_once __DIR__ . '/BaseRoutes.php';

use OpenApi\Attributes as OA;

class ModelImageRoutes extends BaseRoutes
{
    public static function register(): void
    {
        Flight::route('GET /houses/@modelId:[0-9]+/images', [self::class, 'getForModel']);
        Flight::route('POST /houses/@modelId:[0-9]+/images', [self::class, 'create']);
        Flight::route('GET /images/@id:[0-9]+', [self::class, 'getOne']);
        Flight::route('PUT /images/@id:[0-9]+', [self::class, 'replace']);
        Flight::route('PATCH /images/@id:[0-9]+', [self::class, 'patch']);
        Flight::route('DELETE /images/@id:[0-9]+', [self::class, 'delete']);
    }

    private static function service(): ModelImageService
    {
        return Flight::modelImageService();
    }

    #[OA\Get(path: '/houses/{modelId}/images', tags: ['House models'], summary: 'Photos of a model, main photo first',
        parameters: [new OA\Parameter(name: 'modelId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 2)],
        responses: [
            new OA\Response(response: 200, description: 'The photos', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/ModelImage'))),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ])]
    public static function getForModel(string $modelId): void
    {
        self::send(self::service()->getForModel((int) $modelId));
    }

    #[OA\Post(path: '/houses/{modelId}/images', tags: ['House models'], summary: 'Add a photo to a model (the file must already be in frontend/assets/images/)',
        parameters: [new OA\Parameter(name: 'modelId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 2)],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ModelImage')),
        responses: [
            new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(ref: '#/components/schemas/ModelImage')),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ])]
    public static function create(string $modelId): void
    {
        self::send(self::service()->createForModel((int) $modelId, self::body()), 201);
    }

    #[OA\Get(path: '/images/{id}', tags: ['House models'], summary: 'Get one photo',
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'The photo', content: new OA\JsonContent(ref: '#/components/schemas/ModelImage')),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ])]
    public static function getOne(string $id): void
    {
        self::send(self::service()->getById((int) $id));
    }

    #[OA\Put(path: '/images/{id}', tags: ['House models'], summary: 'Replace a photo (all required fields)',
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ModelImage')),
        responses: [
            new OA\Response(response: 200, description: 'Updated', content: new OA\JsonContent(ref: '#/components/schemas/ModelImage')),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ])]
    public static function replace(string $id): void
    {
        self::send(self::service()->update((int) $id, self::body()));
    }

    #[OA\Patch(path: '/images/{id}', tags: ['House models'], summary: 'Change some fields of a photo, e.g. {"sort_order": 0} to make it the main photo',
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ModelImage')),
        responses: [
            new OA\Response(response: 200, description: 'Updated', content: new OA\JsonContent(ref: '#/components/schemas/ModelImage')),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ])]
    public static function patch(string $id): void
    {
        self::send(self::service()->update((int) $id, self::body(), true));
    }

    #[OA\Delete(path: '/images/{id}', tags: ['House models'], summary: 'Delete a photo (only the database row; the file stays on disk)',
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/Deleted'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ])]
    public static function delete(string $id): void
    {
        self::service()->delete((int) $id);
        self::message('Photo deleted.');
    }
}
