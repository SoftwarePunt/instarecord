<?php

namespace SoftwarePunt\Instarecord\Attributes;

use Attribute;

/**
 * Specifies a user-friendly name for a property, used in validation error messages.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class FriendlyName
{
    public function __construct(public string $name)
    {
    }
}