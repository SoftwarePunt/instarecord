<?php

namespace SoftwarePunt\Instarecord\Tests\Samples;

use SoftwarePunt\Instarecord\Attributes\FriendlyName;
use SoftwarePunt\Instarecord\Attributes\MaxLength;
use SoftwarePunt\Instarecord\Attributes\MinLength;
use SoftwarePunt\Instarecord\Attributes\Required;
use SoftwarePunt\Instarecord\Model;

class TestUser extends Model
{
    // -----------------------------------------------------------------------------------------------------------------
    // Actual columns

    public int $id;

    #[MinLength(3)]
    #[MaxLength(6, "Your name is too darn long!")]
    public string $userName;

    #[Required]
    #[FriendlyName("thee date")]
    public \DateTime $joinDate;

    #[Required]
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