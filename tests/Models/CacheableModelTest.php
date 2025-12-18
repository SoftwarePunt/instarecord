<?php

namespace SoftwarePunt\Instarecord\Tests\Models;

use PHPUnit\Framework\TestCase;
use SoftwarePunt\Instarecord\Instarecord;
use SoftwarePunt\Instarecord\Tests\Samples\TestCacheableUser;
use SoftwarePunt\Instarecord\Tests\Samples\TestReadOnlyUser;
use SoftwarePunt\Instarecord\Tests\Samples\TestUser;
use SoftwarePunt\Instarecord\Tests\Testing\TestDatabaseConfig;

class CacheableModelTest extends TestCase
{
    public function testNonCacheableModel(): void
    {
        $stdUser = new TestUser();
        $stdUser->userName = "No Cachey";
        $stdUser->save();

        $fetch = TestUser::fetch($stdUser->id);
        $this->assertNotSame($stdUser, $fetch,
            "Non-cacheable model should not return same instance on re-fetch (after create)");

        $fetch2 = TestUser::fetch($stdUser->id);
        $this->assertNotSame($fetch, $fetch2,
            "Non-cacheable model should not return same instance on re-fetch (fetch & fetch)");
    }

    public function testStaticCache(): void
    {
        $cacheableUser = new TestCacheableUser();
        $cacheableUser->userName = "Little Cachey";
        $cacheableUser->save(); // save hook should commit to cache

        $fetch = TestCacheableUser::fetch($cacheableUser->id);
        $this->assertSame($cacheableUser, $fetch,
            "Cacheable model should return same instance on re-fetch (after create)");

        $fetch2 = TestCacheableUser::fetch($cacheableUser->id);
        $this->assertSame($fetch, $fetch2,
            "Cacheable model should return same instance on re-fetch (fetch & fetch)");
    }
}
