<?php

namespace Softwarepunt\Instarecord\Tests\Samples;

use Softwarepunt\Instarecord\Model;

class AutoColumnTest extends Model
{
    public int $id;
    public \DateTime $createdAt;
    public \DateTime $modifiedAt;
}