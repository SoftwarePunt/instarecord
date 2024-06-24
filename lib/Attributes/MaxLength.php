<?php

namespace SoftwarePunt\Instarecord\Attributes;

use Attribute;
use SoftwarePunt\Instarecord\Validation\ValidationAttribute;
use SoftwarePunt\Instarecord\Validation\ValidationResult;

/**
 * Specifies the maximum length of a model property value.
 *
 * WIP: When used with migrations, this represents the maximum length of the column.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class MaxLength extends ValidationAttribute
{
    public function __construct(public int $maxLength, public ?string $customError = null)
    {
    }

    public function validate(string $name, mixed $value): ValidationResult
    {
        if (empty($value))
            // Do not validate null/empty values
            return ValidationResult::pass();

        if (strlen($value) <= $this->maxLength)
            // Pass: The value is shorter than/equal to the maximum length
            return ValidationResult::pass();

        return ValidationResult::fail(
            $this->customError ?? "{$name} can't be longer than {$this->maxLength} characters"
        );
    }
}