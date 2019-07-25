<?php

namespace Instasell\Instarecord\Models;

use Instasell\Instarecord\InstarecordException;

/**
 * Instarecord exception type for model access violations (e.g. trying to write to a read-only model).
 */
class ModelAccessException extends InstarecordException
{
    // ...
}