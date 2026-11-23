<?php
/* Project endpoints: /projects */

require_once __DIR__ . '/BaseRoutes.php';

use OpenApi\Attributes as OA;

class ProjectRoutes extends BaseRoutes
{
    public static function register(): void
    {
        Flight::route('GET /projects', [self::class, 'getAll']);
        Flight::route('GET /projects/@id:[0-9]+', [self::class, 'getOne']);
        Flight::route('POST /projects', [self::class, 'create']);
        Flight::route('PUT /projects/@id:[0-9]+', [self::class, 'replace']);
        Flight::route('PATCH /projects/@id:[0-9]+', [self::class, 'patch']);
        Flight::route('DELETE /projects/@id:[0-9]+', [self::class, 'delete']);
    }

    private static function service(): ProjectService
    {
        return Flight::projectService();
    }

    #[OA\Get(path: '/projects', tags: ['Projects'], summary: 'List all completed projects with model and location (incl. map coordinates)',
        responses: [
            new OA\Response(response: 200, description: 'The projects', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/ProjectWithDetails'))),
        ])]
    public static function getAll(): void
    {
        self::send(self::service()->getAll());
    }

    #[OA\Get(path: '/projects/{id}', tags: ['Projects'], summary: 'Get one project',
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'The project', content: new OA\JsonContent(ref: '#/components/schemas/Project')),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ])]
    public static function getOne(string $id): void
    {
        self::send(self::service()->getById((int) $id));
    }

    #[OA\Post(path: '/projects', tags: ['Projects'], summary: 'Add a completed project',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/Project')),
        responses: [
            new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(ref: '#/components/schemas/Project')),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
        ])]
    public static function create(): void
    {
        self::send(self::service()->create(self::body()), 201);
    }

    #[OA\Put(path: '/projects/{id}', tags: ['Projects'], summary: 'Replace a project (all required fields)',
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/Project')),
        responses: [
            new OA\Response(response: 200, description: 'Updated', content: new OA\JsonContent(ref: '#/components/schemas/Project')),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ])]
    public static function replace(string $id): void
    {
        self::send(self::service()->update((int) $id, self::body()));
    }

    #[OA\Patch(path: '/projects/{id}', tags: ['Projects'], summary: 'Change some fields of a project, e.g. {"completed_on": "2025-06-30"}',
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/Project')),
        responses: [
            new OA\Response(response: 200, description: 'Updated', content: new OA\JsonContent(ref: '#/components/schemas/Project')),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ])]
    public static function patch(string $id): void
    {
        self::send(self::service()->update((int) $id, self::body(), true));
    }

    #[OA\Delete(path: '/projects/{id}', tags: ['Projects'], summary: 'Delete a project',
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/Deleted'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ])]
    public static function delete(string $id): void
    {
        self::service()->delete((int) $id);
        self::message('Project deleted.');
    }
}
