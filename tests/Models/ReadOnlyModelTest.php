<?php

namespace Instasell\Instarecord\Tests\Models;

use Instasell\Instarecord\Instarecord;
use Instasell\Instarecord\Tests\Samples\ReadOnlyUser;
use Instasell\Instarecord\Tests\Samples\User;
use Instasell\Instarecord\Tests\Testing\TestDatabaseConfig;
use PHPUnit\Framework\TestCase;

class ReadOnlyModelTest extends TestCase
{
    public function testCreateRejectedForReadOnlyModel()
    {
        $this->expectException("Instasell\Instarecord\Models\ModelAccessException");
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

            $this->expectException("Instasell\Instarecord\Models\ModelAccessException");
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

            $this->expectException("Instasell\Instarecord\Models\ModelAccessException");
            $this->expectExceptionMessage("read only model");

            $rou->delete();
        } finally {
            @$normieUser->delete();
        }
    }
}
