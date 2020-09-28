<?php

namespace Instasell\Instarecord\Tests\Samples;

use Instasell\Instarecord\Model;

/**
 * @table users
 */
class UserAutoTest extends Model
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