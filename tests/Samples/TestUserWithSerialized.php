<?php

namespace Instasell\Instarecord\Tests\Samples;

use Instasell\Instarecord\Model;

class TestUserWithSerialized extends Model
{
    // -----------------------------------------------------------------------------------------------------------------
    // Actual columns

    public int $id;
    public ?DummySerializableType $userName;
    public \DateTime $joinDate;

    // -----------------------------------------------------------------------------------------------------------------
    // Test support

    public function getTableName(): string
    {
        return "users";
    }
}