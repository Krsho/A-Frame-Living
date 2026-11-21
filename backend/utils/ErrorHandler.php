<?php
/*
 * ErrorHandler – turns every error into a JSON response with the right status code.
 *
 * Registered in index.php with Flight::map('error', ...), so routes don't need try/catch:
 *
 *   HttpException (thrown by services)      -> its own code, e.g. 400, 404, 409, 503
 *   PDOException "duplicate"/"still in use" -> 409 (a safety net; services check these first)
 *   anything else                           -> 500 "Something went wrong on the server."
 *
 * The client never sees stack traces, SQL or file paths. Unexpected errors are
 * written to backend/logs/error.log instead.
 * In Milestone 4 this becomes part of the error-handling middleware.
 */

class ErrorHandler
{
    public static function handle(Throwable $e): void
    {
        [$status, $body] = self::toResponse($e);

        // Clear anything a route may already have prepared, then send the error.
        // send() is needed here: after an exception FlightPHP does not finish the request itself.
        Flight::response()->clear();
        Flight::json($body, $status, true, 'utf-8', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        Flight::response()->send();
    }

    /** @return array{0: int, 1: array} status code and JSON body */
    private static function toResponse(Throwable $e): array
    {
        if ($e instanceof HttpException) {
            $body = ['error' => $e->getMessage()];
            if ($e->getDetails() !== []) {
                $body['details'] = $e->getDetails();
            }
            return [$e->getStatusCode(), $body];
        }

        // MySQL error codes: 1062 duplicate value, 1451 row still used by another table,
        // 1452 a foreign key points to a row that does not exist.
        $method = $_SERVER['REQUEST_METHOD'] ?? '';
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        if ($e instanceof PDOException) {
            $mysqlCode = (int) ($e->errorInfo[1] ?? 0);
            $known = [
                1062 => [409, 'This value already exists.'],
                1451 => [409, 'This row is still used by other data, so it cannot be deleted.'],
                1452 => [400, 'A referenced row does not exist.'],
            ];
            if (isset($known[$mysqlCode])) {
                Logger::error("{$method} {$path} {$known[$mysqlCode][0]} " . Logger::describe($e));
                return [$known[$mysqlCode][0], ['error' => $known[$mysqlCode][1]]];
            }
        }

        Logger::error("{$method} {$path} 500 " . Logger::describe($e));
        return [500, ['error' => 'Something went wrong on the server. Please try again later.']];
    }
}
