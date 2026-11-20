<?php
/*
 * User endpoints: /users
 * (POST /auth/register and POST /auth/login with JWT come in Milestone 4)
 */

require_once __DIR__ . '/BaseRoutes.php';

use OpenApi\Attributes as OA;

class UserRoutes extends BaseRoutes
{
    public static function register(): void
    {
        Flight::route('GET /users', [self::class, 'getAll']);
        Flight::route('GET /users/@id:[0-9]+', [self::class, 'getOne']);
        Flight::route('GET /users/@id:[0-9]+/inquiries', [self::class, 'getInquiries']);
        Flight::route('POST /users', [self::class, 'create']);
        Flight::route('PUT /users/@id:[0-9]+', [self::class, 'replace']);
        Flight::route('PATCH /users/@id:[0-9]+', [self::class, 'patch']);
        Flight::route('DELETE /users/@id:[0-9]+', [self::class, 'delete']);
    }

    private static function service(): UserService
    {
        return Flight::userService();
    }

    #[OA\Get(path: '/users', tags: ['Users'], summary: 'List all users (never with passwords)',
        responses: [
            new OA\Response(response: 200, description: 'The users', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/User'))),
        ])]
    public static function getAll(): void
    {
        self::send(self::service()->getAll());
    }

    #[OA\Get(path: '/users/{id}', tags: ['Users'], summary: 'Get one user',
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'The user', content: new OA\JsonContent(ref: '#/components/schemas/User')),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ])]
    public static function getOne(string $id): void
    {
        self::send(self::service()->getById((int) $id));
    }

    #[OA\Get(path: '/users/{id}/inquiries', tags: ['Users'], summary: 'Inquiries sent by a user, newest first (the Profile page)',
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'The inquiries', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Inquiry'))),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ])]
    public static function getInquiries(string $id): void
    {
        self::send(self::service()->getInquiries((int) $id));
    }

    #[OA\Post(path: '/users', tags: ['Users'], summary: 'Create a user (the password is stored as a hash)',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UserInput')),
        responses: [
            new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(ref: '#/components/schemas/User')),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
            new OA\Response(response: 409, ref: '#/components/responses/Conflict'),
        ])]
    public static function create(): void
    {
        self::send(self::service()->create(self::body()), 201);
    }

    #[OA\Put(path: '/users/{id}', tags: ['Users'], summary: 'Replace a user (all required fields; password only if it should change)',
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UserInput')),
        responses: [
            new OA\Response(response: 200, description: 'Updated', content: new OA\JsonContent(ref: '#/components/schemas/User')),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 409, ref: '#/components/responses/Conflict'),
        ])]
    public static function replace(string $id): void
    {
        self::send(self::service()->update((int) $id, self::body()));
    }

    #[OA\Patch(path: '/users/{id}', tags: ['Users'], summary: 'Change some fields of a user, e.g. {"phone": "+387 61 123 456"}',
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UserInput')),
        responses: [
            new OA\Response(response: 200, description: 'Updated', content: new OA\JsonContent(ref: '#/components/schemas/User')),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 409, ref: '#/components/responses/Conflict'),
        ])]
    public static function patch(string $id): void
    {
        self::send(self::service()->update((int) $id, self::body(), true));
    }

    #[OA\Delete(path: '/users/{id}', tags: ['Users'], summary: 'Delete a user (their inquiries are kept; the last admin cannot be deleted)',
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/Deleted'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 409, ref: '#/components/responses/Conflict'),
        ])]
    public static function delete(string $id): void
    {
        self::service()->delete((int) $id);
        self::message('User deleted.');
    }
}
