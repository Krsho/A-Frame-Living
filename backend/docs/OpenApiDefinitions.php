<?php
/*
 * OpenAPI definitions shared by all endpoints: the API title, the data
 * schemas of every entity, and the common error responses.
 *
 * Nothing here runs: swagger-php reads these #[OA\...] attributes when
 * GET /openapi.json is requested and turns them into the OpenAPI document
 * that the Swagger page (backend/docs/index.html) displays.
 * The endpoints themselves are documented next to their code in backend/routes/.
 */

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'A-Frame Living API',
    description: 'REST API of A-Frame Living (university web programming project). '
        . 'All requests and responses use JSON. Authentication with JWT and role checks are added in Milestone 4; '
        . 'until then every endpoint is open.'
)]
#[OA\Tag(name: 'House models', description: 'The four A-frame models with prices, features and photos')]
#[OA\Tag(name: 'Packages', description: 'ROH BAU and Turnkey')]
#[OA\Tag(name: 'Features', description: 'Rooms and features such as "Kitchen" or "Fireplace"')]
#[OA\Tag(name: 'Locations', description: 'Places where houses have been built; coordinates from OpenStreetMap Nominatim')]
#[OA\Tag(name: 'Projects', description: 'Completed builds: a model at a location')]
#[OA\Tag(name: 'Inquiries', description: 'Messages from the contact form')]
#[OA\Tag(name: 'Users', description: 'Customer and admin accounts')]
#[OA\Tag(name: 'FAQ', description: 'Questions and answers for the FAQ page')]
#[OA\Tag(name: 'External API', description: 'OpenStreetMap Nominatim (geocoding)')]
// ---------------------------------------------------------------------
// Common responses and parameters
// ---------------------------------------------------------------------
#[OA\Parameter(parameter: 'id', name: 'id', in: 'path', required: true, description: 'Numeric id of the row', schema: new OA\Schema(type: 'integer', minimum: 1), example: 1)]
#[OA\Response(response: 'BadRequest', description: 'Invalid data. "details" has one message per invalid field.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'))]
#[OA\Response(response: 'NotFound', description: 'The row does not exist', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
#[OA\Response(response: 'Conflict', description: 'The request clashes with existing data (e.g. a duplicate value, or the row is still in use)', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
#[OA\Response(response: 'Deleted', description: 'Deleted', content: new OA\JsonContent(ref: '#/components/schemas/Message'))]
#[OA\Response(response: 'ServiceUnavailable', description: 'The external API (Nominatim) is not available', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
#[OA\Response(response: 'ServerError', description: 'Unexpected server error (details are only written to the error log)', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
class OpenApiDefinitions
{
}

// ---------------------------------------------------------------------
// Schemas. swagger-php needs one class per schema, so each schema below
// is placed on its own empty class (e.g. ErrorSchema). The classes are
// never used in code; they only carry the documentation.
// ---------------------------------------------------------------------

// Errors
#[OA\Schema(schema: 'Error', properties: [
    new OA\Property(property: 'error', type: 'string', example: 'House model not found.'),
])]
class ErrorSchema
{
}

#[OA\Schema(schema: 'ValidationError', properties: [
    new OA\Property(property: 'error', type: 'string', example: 'The data you sent is not valid.'),
    new OA\Property(property: 'details', type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'string'), example: ['email' => 'Must be a valid e-mail address.']),
])]
class ValidationErrorSchema
{
}

#[OA\Schema(schema: 'Message', properties: [
    new OA\Property(property: 'message', type: 'string', example: 'Deleted.'),
])]
class MessageSchema
{
}

// ---------------------------------------------------------------------
// Schemas: entities (readOnly fields are only returned, never sent)
// Money and sizes are DECIMAL columns and are returned as strings, e.g. "56.00".
// ---------------------------------------------------------------------
#[OA\Schema(schema: 'HouseModel', required: ['slug', 'name', 'area_m2', 'bedrooms', 'bathrooms', 'footprint_width_m', 'footprint_length_m', 'height_m'], properties: [
    new OA\Property(property: 'id', type: 'integer', readOnly: true, example: 2),
    new OA\Property(property: 'slug', type: 'string', maxLength: 60, pattern: '^[a-z0-9]+(-[a-z0-9]+)*$', example: 'norway'),
    new OA\Property(property: 'name', type: 'string', maxLength: 100, example: 'Norway'),
    new OA\Property(property: 'summary', type: 'string', nullable: true, maxLength: 2000, example: 'Two bedrooms on 56 m² – the model we have built most often.'),
    new OA\Property(property: 'area_m2', type: 'string', format: 'decimal', example: '56.00'),
    new OA\Property(property: 'bedrooms', type: 'integer', minimum: 0, maximum: 20, example: 2),
    new OA\Property(property: 'bathrooms', type: 'integer', minimum: 0, maximum: 20, example: 1),
    new OA\Property(property: 'footprint_width_m', type: 'string', format: 'decimal', example: '5.00'),
    new OA\Property(property: 'footprint_length_m', type: 'string', format: 'decimal', example: '7.00'),
    new OA\Property(property: 'side_room_width_m', type: 'string', format: 'decimal', nullable: true, example: '2.50'),
    new OA\Property(property: 'side_room_length_m', type: 'string', format: 'decimal', nullable: true, example: '4.00'),
    new OA\Property(property: 'height_m', type: 'string', format: 'decimal', example: '5.80'),
    new OA\Property(property: 'is_active', type: 'boolean', description: 'false = hidden from the public site', example: true),
    new OA\Property(property: 'created_at', type: 'string', format: 'date-time', readOnly: true),
    new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', readOnly: true),
])]
class HouseModelSchema
{
}

#[OA\Schema(schema: 'ModelPrice', properties: [
    new OA\Property(property: 'model_id', type: 'integer', example: 2),
    new OA\Property(property: 'package_id', type: 'integer', example: 1),
    new OA\Property(property: 'price_eur', type: 'string', format: 'decimal', example: '22000.00'),
    new OA\Property(property: 'package_code', type: 'string', readOnly: true, example: 'roh_bau'),
    new OA\Property(property: 'package_name', type: 'string', readOnly: true, example: 'ROH BAU'),
])]
class ModelPriceSchema
{
}

#[OA\Schema(schema: 'ModelImage', required: ['file_path', 'alt_text'], properties: [
    new OA\Property(property: 'id', type: 'integer', readOnly: true, example: 5),
    new OA\Property(property: 'model_id', type: 'integer', readOnly: true, example: 2),
    new OA\Property(property: 'file_path', type: 'string', maxLength: 255, description: 'Path inside frontend/', example: 'assets/images/norway/norway1.JPG'),
    new OA\Property(property: 'alt_text', type: 'string', maxLength: 255, example: 'Norway A-frame from the front'),
    new OA\Property(property: 'sort_order', type: 'integer', minimum: 0, description: '0 = main photo', example: 0),
])]
class ModelImageSchema
{
}

#[OA\Schema(schema: 'HouseModelWithPrices', allOf: [
    new OA\Schema(ref: '#/components/schemas/HouseModel'),
    new OA\Schema(properties: [
        new OA\Property(property: 'prices', type: 'array', items: new OA\Items(ref: '#/components/schemas/ModelPrice')),
        new OA\Property(property: 'main_image', ref: '#/components/schemas/ModelImage', nullable: true),
    ]),
])]
class HouseModelWithPricesSchema
{
}

#[OA\Schema(schema: 'HouseModelDetails', allOf: [
    new OA\Schema(ref: '#/components/schemas/HouseModel'),
    new OA\Schema(properties: [
        new OA\Property(property: 'prices', type: 'array', items: new OA\Items(ref: '#/components/schemas/ModelPrice')),
        new OA\Property(property: 'features', type: 'array', items: new OA\Items(ref: '#/components/schemas/Feature')),
        new OA\Property(property: 'images', type: 'array', items: new OA\Items(ref: '#/components/schemas/ModelImage')),
        new OA\Property(property: 'projects', type: 'array', items: new OA\Items(ref: '#/components/schemas/Project')),
    ]),
])]
class HouseModelDetailsSchema
{
}

#[OA\Schema(schema: 'Package', required: ['code', 'name'], properties: [
    new OA\Property(property: 'id', type: 'integer', readOnly: true, example: 1),
    new OA\Property(property: 'code', type: 'string', maxLength: 30, pattern: '^[a-z][a-z0-9_]*$', example: 'roh_bau'),
    new OA\Property(property: 'name', type: 'string', maxLength: 60, example: 'ROH BAU'),
    new OA\Property(property: 'description', type: 'string', nullable: true, maxLength: 2000, example: 'The house is completely finished on the outside.'),
])]
class PackageSchema
{
}

#[OA\Schema(schema: 'Feature', required: ['name', 'category'], properties: [
    new OA\Property(property: 'id', type: 'integer', readOnly: true, example: 4),
    new OA\Property(property: 'name', type: 'string', maxLength: 100, example: 'Fireplace'),
    new OA\Property(property: 'category', type: 'string', enum: ['room', 'construction', 'equipment'], example: 'equipment'),
])]
class FeatureSchema
{
}

#[OA\Schema(schema: 'Location', required: ['city', 'country'], properties: [
    new OA\Property(property: 'id', type: 'integer', readOnly: true, example: 5),
    new OA\Property(property: 'city', type: 'string', maxLength: 100, example: 'Kupres'),
    new OA\Property(property: 'region', type: 'string', nullable: true, maxLength: 100, example: 'Kupres plateau'),
    new OA\Property(property: 'country', type: 'string', maxLength: 100, example: 'Bosnia and Herzegovina'),
    new OA\Property(property: 'latitude', type: 'string', format: 'decimal', nullable: true, description: 'Usually filled in by Nominatim; can be sent together with longitude', example: '43.990000'),
    new OA\Property(property: 'longitude', type: 'string', format: 'decimal', nullable: true, example: '17.270000'),
    new OA\Property(property: 'geocoded_at', type: 'string', format: 'date-time', nullable: true, readOnly: true),
])]
class LocationSchema
{
}

#[OA\Schema(schema: 'GeocodingResult', description: 'What happened when the coordinates were looked up', properties: [
    new OA\Property(property: 'status', type: 'string', enum: ['found', 'not_found', 'failed', 'skipped'], example: 'found'),
    new OA\Property(property: 'message', type: 'string', example: 'Coordinates found: Kupres, Canton 10, Bosnia and Herzegovina'),
])]
class GeocodingResultSchema
{
}

#[OA\Schema(schema: 'LocationWithGeocoding', allOf: [
    new OA\Schema(ref: '#/components/schemas/Location'),
    new OA\Schema(properties: [new OA\Property(property: 'geocoding', ref: '#/components/schemas/GeocodingResult')]),
])]
class LocationWithGeocodingSchema
{
}

#[OA\Schema(schema: 'Place', description: 'A place found by OpenStreetMap Nominatim', properties: [
    new OA\Property(property: 'latitude', type: 'number', example: 43.99),
    new OA\Property(property: 'longitude', type: 'number', example: 17.27),
    new OA\Property(property: 'display_name', type: 'string', example: 'Kupres, Canton 10, Bosnia and Herzegovina'),
])]
class PlaceSchema
{
}

#[OA\Schema(schema: 'Project', required: ['model_id', 'location_id'], properties: [
    new OA\Property(property: 'id', type: 'integer', readOnly: true, example: 5),
    new OA\Property(property: 'model_id', type: 'integer', example: 2),
    new OA\Property(property: 'location_id', type: 'integer', example: 5),
    new OA\Property(property: 'setting', type: 'string', nullable: true, maxLength: 60, example: 'Mountain'),
    new OA\Property(property: 'completed_on', type: 'string', format: 'date', nullable: true, description: 'Not in the future'),
])]
class ProjectSchema
{
}

#[OA\Schema(schema: 'ProjectWithDetails', allOf: [
    new OA\Schema(ref: '#/components/schemas/Project'),
    new OA\Schema(properties: [
        new OA\Property(property: 'model_slug', type: 'string', example: 'norway'),
        new OA\Property(property: 'model_name', type: 'string', example: 'Norway'),
        new OA\Property(property: 'city', type: 'string', example: 'Kupres'),
        new OA\Property(property: 'region', type: 'string', nullable: true, example: 'Kupres plateau'),
        new OA\Property(property: 'country', type: 'string', example: 'Bosnia and Herzegovina'),
        new OA\Property(property: 'latitude', type: 'string', format: 'decimal', nullable: true),
        new OA\Property(property: 'longitude', type: 'string', format: 'decimal', nullable: true),
    ]),
])]
class ProjectWithDetailsSchema
{
}

#[OA\Schema(schema: 'Inquiry', required: ['name', 'email', 'subject', 'message'], properties: [
    new OA\Property(property: 'id', type: 'integer', readOnly: true, example: 3),
    new OA\Property(property: 'user_id', type: 'integer', nullable: true, description: 'Sender, if logged in (from the JWT in Milestone 4)', example: 3),
    new OA\Property(property: 'model_id', type: 'integer', nullable: true, description: 'The model the inquiry is about', example: 4),
    new OA\Property(property: 'name', type: 'string', minLength: 2, maxLength: 120, example: 'Emir Hadžić'),
    new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 150, example: 'emir.hadzic@example.com'),
    new OA\Property(property: 'subject', type: 'string', minLength: 3, maxLength: 150, example: 'Inquiry about the Continental model'),
    new OA\Property(property: 'message', type: 'string', minLength: 10, maxLength: 5000, example: 'Could you send me more information about the fireplace?'),
    new OA\Property(property: 'status', type: 'string', enum: ['new', 'in_progress', 'answered', 'closed'], description: 'Always "new" for a new inquiry; can be changed with PUT/PATCH', example: 'new'),
    new OA\Property(property: 'created_at', type: 'string', format: 'date-time', readOnly: true),
    new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', readOnly: true),
])]
class InquirySchema
{
}

#[OA\Schema(schema: 'User', properties: [
    new OA\Property(property: 'id', type: 'integer', example: 2),
    new OA\Property(property: 'first_name', type: 'string', example: 'Ana'),
    new OA\Property(property: 'last_name', type: 'string', example: 'Kovač'),
    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'ana.kovac@example.com'),
    new OA\Property(property: 'phone', type: 'string', nullable: true, example: '+387 61 000 001'),
    new OA\Property(property: 'role', type: 'string', enum: ['customer', 'admin'], example: 'customer'),
    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
])]
class UserSchema
{
}

#[OA\Schema(schema: 'UserInput', required: ['first_name', 'last_name', 'email', 'password'], description: 'The password is only required for a new user. It is stored as a hash and never returned.', properties: [
    new OA\Property(property: 'first_name', type: 'string', maxLength: 60, example: 'Ana'),
    new OA\Property(property: 'last_name', type: 'string', maxLength: 60, example: 'Kovač'),
    new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 150, example: 'new.customer@example.com'),
    new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8, maxLength: 72, description: 'At least one letter and one digit', example: 'Secret123'),
    new OA\Property(property: 'phone', type: 'string', nullable: true, maxLength: 20, example: '+387 61 000 001'),
    new OA\Property(property: 'role', type: 'string', enum: ['customer', 'admin'], example: 'customer'),
])]
class UserInputSchema
{
}

#[OA\Schema(schema: 'Faq', required: ['category', 'question', 'answer'], properties: [
    new OA\Property(property: 'id', type: 'integer', readOnly: true, example: 3),
    new OA\Property(property: 'category', type: 'string', enum: ['models', 'packages', 'customization', 'locations', 'process'], example: 'models'),
    new OA\Property(property: 'question', type: 'string', minLength: 5, maxLength: 255, example: 'Is the land included in the price?'),
    new OA\Property(property: 'answer', type: 'string', minLength: 5, maxLength: 5000, description: 'Plain text', example: 'No. The prices cover the house only.'),
    new OA\Property(property: 'link_label', type: 'string', nullable: true, maxLength: 100, description: 'Send together with link_url', example: 'See the prices'),
    new OA\Property(property: 'link_url', type: 'string', nullable: true, maxLength: 255, description: 'Starts with #/ or http(s)://', example: '#/houses'),
    new OA\Property(property: 'sort_order', type: 'integer', minimum: 0, example: 3),
])]
class FaqSchema
{
}
