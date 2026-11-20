<?php
/* FAQ endpoints: /faqs */

require_once __DIR__ . '/BaseRoutes.php';

use OpenApi\Attributes as OA;

class FaqRoutes extends BaseRoutes
{
    public static function register(): void
    {
        Flight::route('GET /faqs', [self::class, 'getAll']);
        Flight::route('GET /faqs/@id:[0-9]+', [self::class, 'getOne']);
        Flight::route('POST /faqs', [self::class, 'create']);
        Flight::route('PUT /faqs/@id:[0-9]+', [self::class, 'replace']);
        Flight::route('PATCH /faqs/@id:[0-9]+', [self::class, 'patch']);
        Flight::route('DELETE /faqs/@id:[0-9]+', [self::class, 'delete']);
    }

    private static function service(): FaqService
    {
        return Flight::faqService();
    }

    #[OA\Get(path: '/faqs', tags: ['FAQ'], summary: 'List FAQ entries in the order of the FAQ page',
        parameters: [new OA\Parameter(name: 'category', in: 'query', required: false, description: 'Only this category', schema: new OA\Schema(type: 'string', enum: ['models', 'packages', 'customization', 'locations', 'process']))],
        responses: [
            new OA\Response(response: 200, description: 'FAQ entries', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Faq'))),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
        ])]
    public static function getAll(): void
    {
        self::send(self::service()->getAllFiltered(self::query('category')));
    }

    #[OA\Get(path: '/faqs/{id}', tags: ['FAQ'], summary: 'Get one FAQ entry',
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'The FAQ entry', content: new OA\JsonContent(ref: '#/components/schemas/Faq')),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ])]
    public static function getOne(string $id): void
    {
        self::send(self::service()->getById((int) $id));
    }

    #[OA\Post(path: '/faqs', tags: ['FAQ'], summary: 'Add an FAQ entry',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/Faq')),
        responses: [
            new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(ref: '#/components/schemas/Faq')),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
        ])]
    public static function create(): void
    {
        self::send(self::service()->create(self::body()), 201);
    }

    #[OA\Put(path: '/faqs/{id}', tags: ['FAQ'], summary: 'Replace an FAQ entry (all required fields)',
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/Faq')),
        responses: [
            new OA\Response(response: 200, description: 'Updated', content: new OA\JsonContent(ref: '#/components/schemas/Faq')),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ])]
    public static function replace(string $id): void
    {
        self::send(self::service()->update((int) $id, self::body()));
    }

    #[OA\Patch(path: '/faqs/{id}', tags: ['FAQ'], summary: 'Change some fields of an FAQ entry',
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/Faq')),
        responses: [
            new OA\Response(response: 200, description: 'Updated', content: new OA\JsonContent(ref: '#/components/schemas/Faq')),
            new OA\Response(response: 400, ref: '#/components/responses/BadRequest'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ])]
    public static function patch(string $id): void
    {
        self::send(self::service()->update((int) $id, self::body(), true));
    }

    #[OA\Delete(path: '/faqs/{id}', tags: ['FAQ'], summary: 'Delete an FAQ entry',
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/Deleted'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ])]
    public static function delete(string $id): void
    {
        self::service()->delete((int) $id);
        self::message('FAQ entry deleted.');
    }
}
