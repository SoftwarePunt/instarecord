<?php

namespace Instasell\Instarecord\Tests\Database;

use Instasell\Instarecord\Database\Table;
use PHPUnit\Framework\TestCase;

class TableTest extends TestCase
{
    public function testGetDefaultTableName()
    {
        $this->assertEquals("users", Table::getDefaultTableName("User"));
        $this->assertEquals("my_table_classes", Table::getDefaultTableName("MyTableClass"));
        $this->assertEquals("my_table_classes", Table::getDefaultTableName("myTableClass"));
        $this->assertEquals("my_table_classes", Table::getDefaultTableName("My\\Qualified\\Namespaced\\MyTableClass"));
    }
}