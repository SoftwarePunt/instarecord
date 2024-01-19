<?php

namespace SoftwarePunt\Instarecord\Tests\Samples;


use SoftwarePunt\Instarecord\Model;
use SoftwarePunt\Instarecord\Relationships\Relationship;

class TestPlane extends Model
{
    public int $id;
    #[Relationship(TestAirline::class)]
    public TestAirline $airline;
    public string $name;
    public string $registration;

    public function getTableName(): string
    {
        return "planes";
    }
}