<?php

namespace SoftwarePunt\Instarecord\Attributes;

use Attribute;

/**
 * Specifies a custom name for a model's backing table.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class TableName
{
    public function __construct(public string $name)
    {
    }
}