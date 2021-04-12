<?php

namespace Softwarepunt\Instarecord\Tests\Models;

use PHPUnit\Framework\TestCase;
use Softwarepunt\Instarecord\Instarecord;
use Softwarepunt\Instarecord\Tests\Samples\ReadOnlyUser;
use Softwarepunt\Instarecord\Tests\Samples\User;
use Softwarepunt\Instarecord\Tests\Testing\TestDatabaseConfig;

class ReadOnlyModelTest extends TestCase
{
    public function testCreateRejectedForReadOnlyModel()
    {
        $this->expectException("Softwarepunt\Instarecord\Models\ModelAccessException");
        $this->expectExceptionMessage("read only model");

        Instarecord::config(new TestDatabaseConfig());

        $rou = new ReadOnlyUser();
        $rou->userName = "NewTest";
        $rou->create();
    }

    public function testUpdateRejectedForReadOnlyModel()
    {
        Instarecord::config(new TestDatabaseConfig());

        $normieUser = new User();
        $normieUser->userName = "RejectMe";
        $normieUser->save();

        try {
            $rou = ReadOnlyUser::fetch($normieUser->id);

            $this->assertTrue($rou->update()); // no changes should still pass safely

            $this->expectException("Softwarepunt\Instarecord\Models\ModelAccessException");
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

        $normieUser = new User();
        $normieUser->userName = "RejectMe";
        $normieUser->save();

        try {
            $rou = ReadOnlyUser::fetch($normieUser->id);

            $this->expectException("Softwarepunt\Instarecord\Models\ModelAccessException");
            $this->expectExceptionMessage("read only model");

            $rou->delete();
        } finally {
            @$normieUser->delete();
        }
    }
}
