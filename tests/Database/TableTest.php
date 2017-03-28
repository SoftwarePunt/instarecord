<?php

namespace Instasell\Instarecord\Tests\Database;

use Instasell\Instarecord\Database\Column;
use Instasell\Instarecord\Table;
use PHPUnit\Framework\TestCase;

class TableTest extends TestCase
{
    public function testTranslateColumnName()
    {
        $this->assertEquals("my_simple_name",       Column::getColumNameForProperty("mySimpleName"));
        $this->assertEquals("my_simple_name",       Column::getColumNameForProperty("MySimpleName"));
        $this->assertEquals("mysimplename",         Column::getColumNameForProperty("mysimplename"));
        $this->assertEquals("some4_numbers234",     Column::getColumNameForProperty("Some4Numbers234"));
        $this->assertEquals("test123_string",       Column::getColumNameForProperty("TEST123String"));
    }

    public function testTranslatePropertyName()
    {
        $this->assertEquals("mySimpleName",       Column::getPropertyNameForColumn("my_simple_name"));
    }
}