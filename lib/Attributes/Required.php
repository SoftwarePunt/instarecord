<?php

namespace SoftwarePunt\Instarecord\Attributes;

use Attribute;
use SoftwarePunt\Instarecord\Validation\ValidationAttribute;
use SoftwarePunt\Instarecord\Validation\ValidationResult;

/**
 * Marks a model property as required (not null, empty or whitespace).
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Required extends ValidationAttribute
{
    public function __construct(public ?string $customError = null)
    {
    }

    public function validate(string $name, mixed $value): ValidationResult
    {
        if ($value === null || $value === "" || (is_string($value) && trim($value) === "")
            || $value === false || $value === 0 || (is_array($value) && empty($value)))
            return ValidationResult::fail($this->customError ?? "{$name} is required");

        return ValidationResult::pass();
    }
}