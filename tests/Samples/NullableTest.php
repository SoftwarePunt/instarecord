<?php

namespace Instasell\Instarecord\Tests\Samples;

use Instasell\Instarecord\Model;

class NullableTest extends Model
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $stringNonNullable;

    /**
     * @var string|null
     */
    public $stringNullableThroughType;

    /**
     * @nullable
     * @var string
     */
    public $stringNullableThroughAnnotation;
}