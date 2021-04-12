<?php

namespace Softwarepunt\Instarecord\Tests\Samples;

use Softwarepunt\Instarecord\Model;

class User extends Model
{
    // -----------------------------------------------------------------------------------------------------------------
    // Actual columns

    public int $id;
    public string $userName;
    public \DateTime $joinDate;

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
}