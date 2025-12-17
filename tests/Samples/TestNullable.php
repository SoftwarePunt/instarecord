<?php

namespace SoftwarePunt\Instarecord\Tests\Samples;

use SoftwarePunt\Instarecord\Model;

class TestNullable extends Model
{
    public int $id;
    public string $stringNonNullable;
    public ?string $stringNullableThroughType;
    public string|int $nonNullableUnion;
    public string|int|null $nullableUnion;
}