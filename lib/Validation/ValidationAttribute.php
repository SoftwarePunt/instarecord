<?php

namespace SoftwarePunt\Instarecord\Validation;

abstract class ValidationAttribute
{
    /**
     * Validate the given value.
     *
     * @param string $name The "friendly name" of the property being validated. Should be used in error messages.
     * @param mixed $value The property value to validate.
     * @return ValidationResult The result of the validation, created from `ValidationResult::pass()` or `ValidationResult::fail()`.
     */
    public abstract function validate(string $name, mixed $value): ValidationResult;

    /**
     * Performs validation, only returning the pass/fail result.
     */
    public function checkValue(mixed $value): bool
    {
        return $this->validate("", $value)->ok;
    }
}