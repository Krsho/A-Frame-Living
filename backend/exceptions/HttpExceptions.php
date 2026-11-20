<?php
/*
 * Exceptions that know which HTTP status code they belong to.
 *
 * A service throws one of these when a request cannot be fulfilled, e.g.
 *   throw new NotFoundException('House model not found.');
 * The error handler in backend/index.php turns it into a JSON response:
 *   404  {"error": "House model not found."}
 *
 * This keeps the routes short: they don't need an if/else for every error case.
 */

class HttpException extends RuntimeException
{
    /**
     * @param int   $statusCode HTTP status code sent to the client
     * @param array $details    Extra information, e.g. one message per invalid field
     */
    public function __construct(string $message, private int $statusCode, private array $details = [])
    {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getDetails(): array
    {
        return $this->details;
    }
}

/** 400 Bad Request – the data sent by the client is invalid. $errors = ['field' => 'message']. */
class ValidationException extends HttpException
{
    public function __construct(array $errors, string $message = 'The data you sent is not valid.')
    {
        parent::__construct($message, 400, $errors);
    }
}

/** 404 Not Found – the requested row does not exist. */
class NotFoundException extends HttpException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 404);
    }
}

/** 409 Conflict – the request clashes with existing data, e.g. an e-mail that is already registered. */
class ConflictException extends HttpException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 409);
    }
}

/** 503 Service Unavailable – the external API (Nominatim) did not answer or returned an error. */
class ExternalServiceException extends HttpException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 503);
    }
}
