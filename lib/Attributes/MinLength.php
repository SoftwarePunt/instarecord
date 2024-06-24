<?php

namespace SoftwarePunt\Instarecord\Attributes;

use Attribute;
use SoftwarePunt\Instarecord\Validation\ValidationAttribute;
use SoftwarePunt\Instarecord\Validation\ValidationResult;

/**
 * Specifies the minimum length of a model property value.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class MinLength extends ValidationAttribute
{
    public function __construct(public int $minLength, public ?string $customError = null)
    {
    }

    public function validate(string $name, mixed $value): ValidationResult
    {
        if (!empty($value) && strlen($value) >= $this->minLength)
            // Pass: The value is not null/empty and is at least the minimum length
            return ValidationResult::pass();

        return ValidationResult::fail(
            $this->customError ?? "{$name} must be at least {$this->minLength} characters"
        );
    }
}