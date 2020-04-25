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

    public ?string $stringNullableThroughType;

    /**
     * @nullable
     * @var string
     */
    public $stringNullableThroughAnnotation;
}