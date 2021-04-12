<?php

namespace SoftwarePunt\Instarecord\Tests\Samples;

use SoftwarePunt\Instarecord\Model;

class AutoColumnTest extends Model
{
    public int $id;
    public \DateTime $createdAt;
    public \DateTime $modifiedAt;
}