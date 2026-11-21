<?php
/* Inquiry endpoints: /inquiries */

require_once __DIR__ . '/BaseRoutes.php';

use OpenApi\Attributes as OA;

class InquiryRoutes extends BaseRoutes
{
    public static function register(): void
    {
        Flight::route('GET /inquiries', [self::class, 'getAll']);
        Flight::route('GET /inquiries/@id:[0-9]+', [self::class, 'getOne']);
        Flight::route('POST /inquiries', [self::class, 'create']);
        Flight::route('PUT /inquiries/@id:[0-9]+', [self::class, 'replace']);
        Flight::route('PATCH /inquiries/@id:[0-9]+', [self::class, 'patch']);
        Flight::route('DELETE /inquiries/@id:[0-9]+', [self::class, 'delete']);
    }

    private static function service(): InquiryService
    {
        return Flight::inquiryService();
    }

    #[OA\Get(path: '/inquiries', tags: ['Inquiries'], summary: 'List inquiries, newest first',
        parameters: [new OA\Parameter(name: 'status', in: 'query', required: false, description: 'Only this status', schema: new OA\Schema(type: 'string', enum: ['new', 'in_progress', 'answered', 'closed']))],
        responses: [
            new OA\Response(response: 200, description: 'The inquiries', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Inquiry'))),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
        ])]
    public static function getAll(): void
    {
        self::send(self::service()->getAllFiltered(self::query('status')));
    }

    #[OA\Get(path: '/inquiries/{id}', tags: ['Inquiries'], summary: 'Get one inquiry',
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'The inquiry', content: new OA\JsonContent(ref: '#/components/schemas/Inquiry')),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ])]
    public static function getOne(string $id): void
    {
        self::send(self::service()->getById((int) $id));
    }

    #[OA\Post(path: '/inquiries', tags: ['Inquiries'], summary: 'Send an inquiry (contact form). The status is always "new".',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/Inquiry')),
        responses: [
            new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(ref: '#/components/schemas/Inquiry')),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
        ])]
    public static function create(): void
    {
        self::send(self::service()->create(self::body()), 201);
    }

    #[OA\Put(path: '/inquiries/{id}', tags: ['Inquiries'], summary: 'Replace an inquiry (all required fields)',
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/Inquiry')),
        responses: [
            new OA\Response(response: 200, description: 'Updated', content: new OA\JsonContent(ref: '#/components/schemas/Inquiry')),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ])]
    public static function replace(string $id): void
    {
        self::send(self::service()->update((int) $id, self::body()));
    }

    #[OA\Patch(path: '/inquiries/{id}', tags: ['Inquiries'], summary: 'Change some fields, e.g. the status: {"status": "answered"}',
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/Inquiry')),
        responses: [
            new OA\Response(response: 200, description: 'Updated', content: new OA\JsonContent(ref: '#/components/schemas/Inquiry')),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ])]
    public static function patch(string $id): void
    {
        self::send(self::service()->update((int) $id, self::body(), true));
    }

    #[OA\Delete(path: '/inquiries/{id}', tags: ['Inquiries'], summary: 'Delete an inquiry',
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/Deleted'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ])]
    public static function delete(string $id): void
    {
        self::service()->delete((int) $id);
        self::message('Inquiry deleted.');
    }
}
