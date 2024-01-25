<?php

namespace SoftwarePunt\Instarecord\Tests\Samples;

use SoftwarePunt\Instarecord\Model;

class TestNullable extends Model
{
    public int $id;
    public string $stringNonNullable;
    public ?string $stringNullableThroughType;
}