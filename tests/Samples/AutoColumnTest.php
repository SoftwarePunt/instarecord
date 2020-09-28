<?php

namespace Instasell\Instarecord\Tests\Samples;

use Instasell\Instarecord\Model;

class AutoColumnTest extends Model
{
    public int $id;
    public \DateTime $createdAt;
    public \DateTime $modifiedAt;
}