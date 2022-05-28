<?php

namespace SoftwarePunt\Instarecord\Models;

use SoftwarePunt\Instarecord\InstarecordException;

/**
 * Instarecord exception type for model logic violations (e.g. trying to reload a model that has no PK value set).
 */
class ModelLogicException extends InstarecordException
{
    // ...
}