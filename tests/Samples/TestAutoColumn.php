<?php

namespace SoftwarePunt\Instarecord\Tests\Samples;

use SoftwarePunt\Instarecord\Model;

class TestAutoColumn extends Model
{
    public int $id;
    public \DateTime $createdAt;
    public \DateTime $modifiedAt;
}