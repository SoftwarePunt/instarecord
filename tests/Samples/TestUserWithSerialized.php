<?php

namespace Softwarepunt\Instarecord\Tests\Samples;

use Softwarepunt\Instarecord\Model;

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