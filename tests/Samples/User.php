<?php

namespace Instasell\Instarecord\Tests\Samples;

use Instasell\Instarecord\Model;


class User extends Model
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