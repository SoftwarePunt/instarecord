<?php

namespace Softwarepunt\Instarecord\Tests\Samples;

use Softwarepunt\Instarecord\Model;

class DefaultsTest extends Model
{
    public int $id;
    public ?string $strNullableWithDefault = "hello1";
    public string $strNonNullableWithDefault = "hello2";
    public ?string $strDefaultNullValue = null;
}