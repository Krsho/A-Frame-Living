<?php
/*
 * Validator – checks the data a client sent before a service saves it.
 *
 * Usage in a service:
 *
 *   $clean = (new Validator($data, $partial))
 *       ->string('name', required: true, max: 100)
 *       ->enum('category', ['room', 'construction', 'equipment'], required: true)
 *       ->validate();
 *
 * - Every rule describes one field. Fields that were not described are "unknown"
 *   and rejected, so a typo like "nmae" is reported instead of silently ignored.
 * - $partial = true is for PATCH: missing fields are fine, only the sent fields are checked.
 * - validate() returns only the checked fields (strings trimmed, numbers converted),
 *   or throws a ValidationException (HTTP 400) with one message per invalid field.
 */

class Validator
{
    private array $clean = [];
    private array $errors = [];
    private array $knownFields = [];

    public function __construct(private array $data, private bool $partial = false)
    {
    }

    // ---------------------------------------------------------------
    // Rules
    // ---------------------------------------------------------------

    /** Text with a length limit and an optional regular expression. */
    public function string(string $field, bool $required = false, int $min = 1, int $max = 255, ?string $pattern = null, string $patternMessage = 'Has an invalid format.'): static
    {
        return $this->rule($field, $required, function ($value) use ($min, $max, $pattern, $patternMessage) {
            if (!is_string($value) && !is_int($value) && !is_float($value)) {
                return [false, 'Must be text.'];
            }
            $value = trim((string) $value);
            $length = mb_strlen($value);
            if ($length < $min) {
                return [false, "Must be at least {$min} characters long."];
            }
            if ($length > $max) {
                return [false, "Must be at most {$max} characters long."];
            }
            if ($pattern !== null && !preg_match($pattern, $value)) {
                return [false, $patternMessage];
            }
            return [true, $value];
        });
    }

    public function email(string $field, bool $required = false, int $max = 150): static
    {
        return $this->rule($field, $required, function ($value) use ($max) {
            $value = is_string($value) ? trim($value) : '';
            if (!filter_var($value, FILTER_VALIDATE_EMAIL) || mb_strlen($value) > $max) {
                return [false, 'Must be a valid e-mail address.'];
            }
            return [true, strtolower($value)];
        });
    }

    /** Whole number, e.g. 3 or "3". */
    public function integer(string $field, bool $required = false, int $min = 0, int $max = PHP_INT_MAX): static
    {
        return $this->rule($field, $required, function ($value) use ($min, $max) {
            $number = filter_var($value, FILTER_VALIDATE_INT);
            if ($number === false || is_bool($value)) {
                return [false, 'Must be a whole number.'];
            }
            if ($number < $min || $number > $max) {
                return [false, "Must be between {$min} and {$max}."];
            }
            return [true, $number];
        });
    }

    /** Number with decimals, e.g. 56 or 5.8. Stored as a string so DECIMAL columns keep their exact value. */
    public function decimal(string $field, bool $required = false, float $min = 0, float $max = 99999999.99, int $decimals = 2): static
    {
        return $this->rule($field, $required, function ($value) use ($min, $max, $decimals) {
            if (is_bool($value) || !is_numeric($value)) {
                return [false, 'Must be a number.'];
            }
            $number = (float) $value;
            if ($number < $min || $number > $max) {
                return [false, "Must be between {$min} and {$max}."];
            }
            return [true, number_format($number, $decimals, '.', '')];
        });
    }

    /** true/false (also accepts 1/0). Stored as 1 or 0. */
    public function boolean(string $field, bool $required = false): static
    {
        return $this->rule($field, $required, function ($value) {
            $bool = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($bool === null) {
                return [false, 'Must be true or false.'];
            }
            return [true, $bool ? 1 : 0];
        });
    }

    /** One value from a fixed list, e.g. an inquiry status. */
    public function enum(string $field, array $allowed, bool $required = false): static
    {
        return $this->rule($field, $required, function ($value) use ($allowed) {
            if (!in_array($value, $allowed, true)) {
                return [false, 'Must be one of: ' . implode(', ', $allowed) . '.'];
            }
            return [true, $value];
        });
    }

    /** Date in the format YYYY-MM-DD. */
    public function date(string $field, bool $required = false): static
    {
        return $this->rule($field, $required, function ($value) {
            $date = is_string($value) ? DateTime::createFromFormat('!Y-m-d', $value) : false;
            if ($date === false || $date->format('Y-m-d') !== $value) {
                return [false, 'Must be a date in the format YYYY-MM-DD.'];
            }
            return [true, $value];
        });
    }

    /** Password: 8–72 characters (72 is the limit of PHP's bcrypt), with at least one letter and one digit. */
    public function password(string $field, bool $required = false): static
    {
        return $this->rule($field, $required, function ($value) {
            if (!is_string($value) || strlen($value) < 8 || strlen($value) > 72) {
                return [false, 'Must be 8 to 72 characters long.'];
            }
            if (!preg_match('/[A-Za-z]/', $value) || !preg_match('/\d/', $value)) {
                return [false, 'Must contain at least one letter and one digit.'];
            }
            return [true, $value]; // not trimmed: spaces are allowed in passwords
        });
    }

    /** Adds an error found by the service itself, e.g. "this model does not exist". */
    public function addError(string $field, string $message): static
    {
        $this->errors[$field] = $message;
        return $this;
    }

    // ---------------------------------------------------------------
    // Result
    // ---------------------------------------------------------------

    /** Returns the cleaned data, or throws a ValidationException (HTTP 400). */
    public function validate(): array
    {
        foreach (array_keys($this->data) as $field) {
            if (!in_array($field, $this->knownFields, true)) {
                $this->errors[$field] = 'Unknown field.';
            }
        }

        if ($this->errors === [] && $this->clean === []) {
            $this->errors['body'] = 'Send at least one field.';
        }

        if ($this->errors !== []) {
            throw new ValidationException($this->errors);
        }

        return $this->clean;
    }

    // ---------------------------------------------------------------
    // Shared logic of all rules
    // ---------------------------------------------------------------

    /**
     * @param callable $check function($value): [bool $ok, mixed $cleanValueOrErrorMessage]
     */
    private function rule(string $field, bool $required, callable $check): static
    {
        $this->knownFields[] = $field;

        // Field not sent at all.
        if (!array_key_exists($field, $this->data)) {
            if ($required && !$this->partial) {
                $this->errors[$field] = 'This field is required.';
            }
            return $this;
        }

        // Field sent but empty: null for optional fields, an error for required ones.
        $value = $this->data[$field];
        if ($value === null || (is_string($value) && trim($value) === '')) {
            if ($required) {
                $this->errors[$field] = 'This field is required.';
            } else {
                $this->clean[$field] = null;
            }
            return $this;
        }

        [$ok, $result] = $check($value);
        if ($ok) {
            $this->clean[$field] = $result;
        } else {
            $this->errors[$field] = $result;
        }
        return $this;
    }
}
