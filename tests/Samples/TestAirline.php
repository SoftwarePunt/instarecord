<?php

namespace SoftwarePunt\Instarecord\Tests\Samples;

use SoftwarePunt\Instarecord\Model;
use SoftwarePunt\Instarecord\Relationships\HasManyRelationship;

class TestAirline extends Model
{
    public int $id;
    public string $name;
    public string $iataCode;

    public function getTableName(): string
    {
        return "airlines";
    }

    public function planes(): HasManyRelationship
    {
        return $this->hasMany(TestPlane::class);
    }
}