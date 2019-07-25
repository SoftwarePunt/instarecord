<?php

namespace Instasell\Instarecord\Tests\Samples;

use Instasell\Instarecord\Model;

class AutoColumnTest extends Model
{
    /**
     * @var int
     */
    public $id;

    /**
     * @auto created
     */
    public $createdAt;

    /**
     * @auto modified
     */
    public $modifiedAt;
}