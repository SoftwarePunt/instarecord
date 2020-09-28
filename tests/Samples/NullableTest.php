<?php

namespace Instasell\Instarecord\Tests\Samples;

use Instasell\Instarecord\Model;

class NullableTest extends Model
{
    public int $id;

    public string $stringNonNullable;

    public ?string $stringNullableThroughType;
}