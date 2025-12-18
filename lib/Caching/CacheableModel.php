<?php

namespace SoftwarePunt\Instarecord\Caching;

use Attribute;

/**
 * Specifies that a given model may be cached, and may specify specific caching behavior.
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class CacheableModel
{
    public function __construct(public ModelCacheMode $mode = ModelCacheMode::STATIC)
    {
    }
}