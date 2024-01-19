<?php

namespace SoftwarePunt\Instarecord\Tests\Samples;

use SoftwarePunt\Instarecord\Model;

class TestUser extends Model
{
    // -----------------------------------------------------------------------------------------------------------------
    // Actual columns

    public int $id;
    public string $userName;
    public \DateTime $joinDate;
    public TestEnum $enumValue;

    // -----------------------------------------------------------------------------------------------------------------
    // Not columns

    private int $secretNotWritable;

    // -----------------------------------------------------------------------------------------------------------------
    // Test support

    private bool $useAutoIncrement = true;

    public function setUseAutoIncrement(bool $useAutoIncrement): void
    {
        $this->useAutoIncrement = $useAutoIncrement;
    }

    public function getIsAutoIncrement(): bool
    {
        return $this->useAutoIncrement;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Table name

    public function getTableName(): string
    {
        return "users";
    }
}