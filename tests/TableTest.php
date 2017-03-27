<?php

namespace Instasell\Instarecord\Tests;

use Instasell\Instarecord\Table;
use PHPUnit\Framework\TestCase;

class TableTest extends TestCase
{
    public function testTranslateColumnName()
    {
        $this->assertEquals("my_simple_name",       Table::translateColumnName("mySimpleName"));
        $this->assertEquals("my_simple_name",       Table::translateColumnName("MySimpleName"));
        $this->assertEquals("mysimplename",         Table::translateColumnName("mysimplename"));
        $this->assertEquals("some4_numbers234",     Table::translateColumnName("Some4Numbers234"));
        $this->assertEquals("test123_string",       Table::translateColumnName("TEST123String"));
    }
}