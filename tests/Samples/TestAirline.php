<?php

namespace SoftwarePunt\Instarecord\Tests\Samples;

use SoftwarePunt\Instarecord\Model;

class TestAirline extends Model
{
    public int $id;
    public string $name;
    public string $iataCode;

    public function getTableName(): string
    {
        return "airlines";
    }
}