<?php

namespace Softwarepunt\Instarecord\Tests\Samples;

use Softwarepunt\Instarecord\Model;

class NullableTest extends Model
{
    public int $id;
    public string $stringNonNullable;
    public ?string $stringNullableThroughType;
}