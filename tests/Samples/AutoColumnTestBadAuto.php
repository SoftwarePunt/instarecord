<?php

namespace Instasell\Instarecord\Tests\Samples;

use Instasell\Instarecord\Model;

class AutoColumnTestBadAuto extends Model
{
    /**
     * @auto blah
     */
    public $createdAt;
}