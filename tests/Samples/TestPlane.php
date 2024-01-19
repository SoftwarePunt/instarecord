<?php

namespace SoftwarePunt\Instarecord\Tests\Samples;

use SoftwarePunt\Instarecord\Model;

class TestPlane extends Model
{
    public int $id;
    public TestAirline $airline;
    public string $name;
    public string $registration;

    public function getTableName(): string
    {
        return "planes";
    }
}