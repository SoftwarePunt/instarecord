<?php

namespace Instasell\Instarecord\Tests\Samples;

use Instasell\Instarecord\Model;
use Instasell\Instarecord\Models\IReadOnlyModel;

/**
 * @table users
 */
class ReadOnlyUser extends Model implements IReadOnlyModel
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