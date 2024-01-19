<?php

namespace SoftwarePunt\Instarecord\Tests\Samples;

use SoftwarePunt\Instarecord\Model;

class TestDefaults extends Model
{
    public int $id;
    public ?string $strNullableWithDefault = "hello1";
    public string $strNonNullableWithDefault = "hello2";
    public ?string $strDefaultNullValue = null;
    public TestEnum $enumWithDefault = TestEnum::Three;
}