<?php

namespace Instasell\Instarecord\Tests\Samples;

use Instasell\Instarecord\Model;

class User extends Model
{
    private int $secretNotWritable;

    public int $id;

    /**
     * @myCustomAnnotation
     */
    public string $userName;

    public \DateTime $joinDate;
}