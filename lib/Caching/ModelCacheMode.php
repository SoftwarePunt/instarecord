<?php

namespace SoftwarePunt\Instarecord\Caching;

enum ModelCacheMode
{
    /**
     * None cache mode: disable all caching behavior for this model.
     * This is the equivalent of omitting the CacheableModel attribute.
     */
    case NONE;
    /**
     * Static cache mode: model is persisted only during current script execution.
     */
    case STATIC;
}
