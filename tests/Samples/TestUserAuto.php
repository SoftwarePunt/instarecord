<?php

namespace SoftwarePunt\Instarecord\Tests\Samples;

use SoftwarePunt\Instarecord\Attributes\TableName;
use SoftwarePunt\Instarecord\Model;

#[TableName("users")]
class TestUserAuto extends Model
{
    private int $secretNotWritable;

    public int $id;

    public string $userName;

    public \DateTime $joinDate;

    public \DateTime $modifiedAt;

    public \DateTime $createdAt;
}