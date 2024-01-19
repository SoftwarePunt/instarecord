<?php

namespace SoftwarePunt\Instarecord\Tests\Samples;

use SoftwarePunt\Instarecord\Model;
use SoftwarePunt\Instarecord\Relationships\Relationship;

class TestAirline extends Model
{
    public int $id;
    public string $name;
    public string $iataCode;

    /**
     * @var TestPlane[]
     */
    #[Relationship(TestPlane::class)]
    public array $planes;

    public function getTableName(): string
    {
        return "airlines";
    }
}