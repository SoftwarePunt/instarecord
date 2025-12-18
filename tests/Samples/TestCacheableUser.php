<?php

namespace SoftwarePunt\Instarecord\Tests\Samples;

use SoftwarePunt\Instarecord\Caching\CacheableModel;
use SoftwarePunt\Instarecord\Caching\ModelCacheMode;
use SoftwarePunt\Instarecord\Model;

#[CacheableModel(ModelCacheMode::STATIC)]
class TestCacheableUser extends Model
{
    // -----------------------------------------------------------------------------------------------------------------
    // Actual columns

    public int $id;
    public string $userName;

    // -----------------------------------------------------------------------------------------------------------------
    // Table name

    public function getTableName(): string
    {
        return "users";
    }
}