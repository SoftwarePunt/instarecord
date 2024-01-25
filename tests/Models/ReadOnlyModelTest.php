<?php

namespace SoftwarePunt\Instarecord\Tests\Models;

use PHPUnit\Framework\TestCase;
use SoftwarePunt\Instarecord\Instarecord;
use SoftwarePunt\Instarecord\Tests\Samples\TestReadOnlyUser;
use SoftwarePunt\Instarecord\Tests\Samples\TestUser;
use SoftwarePunt\Instarecord\Tests\Testing\TestDatabaseConfig;

class ReadOnlyModelTest extends TestCase
{
    public function testCreateRejectedForReadOnlyModel()
    {
        $this->expectException("SoftwarePunt\Instarecord\Models\ModelAccessException");
        $this->expectExceptionMessage("read only model");

        Instarecord::config(new TestDatabaseConfig());

        $rou = new TestReadOnlyUser();
        $rou->userName = "NewTest";
        $rou->create();
    }

    public function testUpdateRejectedForReadOnlyModel()
    {
        Instarecord::config(new TestDatabaseConfig());

        $normieUser = new TestUser();
        $normieUser->userName = "RejectMe";
        $normieUser->save();

        try {
            $rou = TestReadOnlyUser::fetch($normieUser->id);

            $this->assertTrue($rou->update()); // no changes should still pass safely

            $this->expectException("SoftwarePunt\Instarecord\Models\ModelAccessException");
            $this->expectExceptionMessage("read only model");

            $rou->userName = "edited";
            $rou->update();
        } finally {
            @$normieUser->delete();
        }
    }

    public function testDeleteRejectedForReadOnlyModel()
    {
        Instarecord::config(new TestDatabaseConfig());

        $normieUser = new TestUser();
        $normieUser->userName = "RejectMe";
        $normieUser->save();

        try {
            $rou = TestReadOnlyUser::fetch($normieUser->id);

            $this->expectException("SoftwarePunt\Instarecord\Models\ModelAccessException");
            $this->expectExceptionMessage("read only model");

            $rou->delete();
        } finally {
            @$normieUser->delete();
        }
    }
}
