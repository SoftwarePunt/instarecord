<?php

namespace Instasell\Instarecord\Tests\Samples;

use Instasell\Instarecord\Model;

class DefaultsTest extends Model
{
    /**
     * @var int
     */
    public $id;

    /**
     * @default hello1
     * @nullable
     * @var null|string
     */
    public $strNullableWithDefault;

    /**
     * @default hello2
     * @var string
     */
    public $strNonNullableWithDefault;

    /**
     * @default null
     * @var string
     */
    public $strDefaultNullValue;
}