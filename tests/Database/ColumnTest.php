<?php

namespace Instasell\Instarecord\Tests\Database;

use Instasell\Instarecord\Database\Column;
use Instasell\Instarecord\Table;
use PHPUnit\Framework\TestCase;

class ColumnTest extends TestCase
{
    public function testTranslateColumnName()
    {
        $this->assertEquals("my_simple_name",       Column::getDefaultColumnName("my_simple_name"));
        $this->assertEquals("my_simple_name",       Column::getDefaultColumnName("mySimpleName"));
        $this->assertEquals("my_simple_name",       Column::getDefaultColumnName("MySimpleName"));
        $this->assertEquals("mysimplename",         Column::getDefaultColumnName("mysimplename"));
        $this->assertEquals("some4_numbers234",     Column::getDefaultColumnName("Some4Numbers234"));
        $this->assertEquals("test123_string",       Column::getDefaultColumnName("TEST123String"));
    }
}