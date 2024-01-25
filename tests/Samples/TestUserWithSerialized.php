<?php

namespace SoftwarePunt\Instarecord\Tests\Samples;

use SoftwarePunt\Instarecord\Model;

class TestUserWithSerialized extends Model
{
    // -----------------------------------------------------------------------------------------------------------------
    // Actual columns

    public int $id;
    public ?TestDummySerializableType $userName;
    public \DateTime $joinDate;

    // -----------------------------------------------------------------------------------------------------------------
    // Test support

    public function getTableName(): string
    {
        return "users";
    }
}