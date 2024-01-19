<?php

namespace SoftwarePunt\Instarecord\Tests\Samples;

use SoftwarePunt\Instarecord\Model;
use SoftwarePunt\Instarecord\Models\IReadOnlyModel;

/**
 * @table users
 */
class TestReadOnlyUser extends Model implements IReadOnlyModel
{
    private int $secretNotWritable;
    public int $id;
    public string $userName;
    public \DateTime $joinDate;

    public function getTableName(): string
    {
        return "users";
    }
}