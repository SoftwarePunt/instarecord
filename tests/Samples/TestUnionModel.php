<?php

namespace SoftwarePunt\Instarecord\Tests\Samples;

use SoftwarePunt\Instarecord\Model;

class TestUnionModel extends Model
{
    public string|TestBackedEnum|int|null $whoKnows;
    public int|null $intOrNull;
    public float|int|bool $prefScalar;
}