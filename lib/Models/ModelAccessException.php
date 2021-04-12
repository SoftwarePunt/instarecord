<?php

namespace SoftwarePunt\Instarecord\Models;

use SoftwarePunt\Instarecord\InstarecordException;

/**
 * Instarecord exception type for model access violations (e.g. trying to write to a read-only model).
 */
class ModelAccessException extends InstarecordException
{
    // ...
}