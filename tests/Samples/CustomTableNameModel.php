<?php

namespace Instasell\Instarecord\Tests\Samples;

use Instasell\Instarecord\Model;

/**
 * @table my_name
 */
class CustomTableNameModel extends Model
{
    /**
     * @var int
     */
    public $id;
}