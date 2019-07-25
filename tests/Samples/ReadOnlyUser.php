<?php

namespace Instasell\Instarecord\Tests\Samples;

use Instasell\Instarecord\Model;
use Instasell\Instarecord\Models\IReadOnlyModel;

/**
 * @table users
 */
class ReadOnlyUser extends Model implements IReadOnlyModel
{
    /**
     * @var int
     */
    private $secretNotWritable;

    /**
     * @var int
     */
    public $id;

    /**
     * @myCustomAnnotation
     * @var string
     */
    public $userName;

    /**
     * @var \DateTime
     */
    public $joinDate;
}