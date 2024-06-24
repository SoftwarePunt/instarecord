<?php

namespace SoftwarePunt\Instarecord\Validation;

class ValidationResult
{
    /**
     * Indicates if the validation passed successfully.
     */
    public readonly bool $ok;
    /**
     * Error message if the validation failed.
     * May only be null if the validation passed.
     */
    public readonly ?string $message;

    private function __construct(bool $ok, ?string $message)
    {
        $this->ok = $ok;
        $this->message = $message;
    }

    public static function pass(): ValidationResult
    {
        return new ValidationResult(true, null);
    }

    public static function fail(string $message): ValidationResult
    {
        return new ValidationResult(false, $message);
    }
}