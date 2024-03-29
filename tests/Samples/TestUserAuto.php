<?php

namespace SoftwarePunt\Instarecord\Tests\Samples;

use SoftwarePunt\Instarecord\Model;

/**
 * @table users
 */
class TestUserAuto extends Model
{
    private int $secretNotWritable;

    public int $id;

    public string $userName;

    public \DateTime $joinDate;

    public \DateTime $modifiedAt;

    public \DateTime $createdAt;

    public function getTableName(): string
    {
        return "users";
    }
}