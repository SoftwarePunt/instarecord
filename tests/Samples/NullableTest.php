<?php

namespace SoftwarePunt\Instarecord\Tests\Samples;

use SoftwarePunt\Instarecord\Model;

class NullableTest extends Model
{
    public int $id;
    public string $stringNonNullable;
    public ?string $stringNullableThroughType;
}