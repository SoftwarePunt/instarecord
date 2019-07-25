<?php

namespace Instasell\Instarecord\Tests\Samples;

use Instasell\Instarecord\Model;

/**
 * @table users
 */
class UserAutoTest extends Model
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

    /**
     * @auto modified
     */
    public $modifiedAt;

    /**
     * @auto created
     */
    public $createdAt;
}