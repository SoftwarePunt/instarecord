<?php

namespace SoftwarePunt\Instarecord\Tests\Database;

use PHPUnit\Framework\TestCase;
use SoftwarePunt\Instarecord\Database\Table;

class TableTest extends TestCase
{
    public function testDefaultTableNameGeneration()
    {
        $this->assertEquals("users", Table::getDefaultTableName("User"));
        $this->assertEquals("test_users", Table::getDefaultTableName("TestUser"));
        $this->assertEquals("my_table_classes", Table::getDefaultTableName("MyTableClass"));
        $this->assertEquals("my_table_classes", Table::getDefaultTableName("myTableClass"));
        $this->assertEquals("my_table_classes", Table::getDefaultTableName("My\\Qualified\\Namespaced\\MyTableClass"));
    }

    public function testConstructorErrorsOnBadClassName()
    {
        $this->expectException("SoftwarePunt\Instarecord\Config\ConfigException");
        $this->expectExceptionMessage("invalid class");

        $table = new Table('bogus\\class\\not\\real');
    }

    public function testConstructorErrorsOnNonModelClassName()
    {
        $this->expectException("SoftwarePunt\Instarecord\Config\ConfigException");
        $this->expectExceptionMessage("does not extend");

        $table = new Table('SoftwarePunt\\Instarecord\\Tests\\Database\\TableTest');
    }

    public function testExtractsIndexedColumnList()
    {
        $table = new Table('SoftwarePunt\\Instarecord\\Tests\\Samples\\TestUser');
        $columns = $table->getColumns();

        $this->assertNotEmpty($columns, 'Expected a non-empty columns list');
        $this->assertArrayHasKey('userName', $columns, 'Columns list should be indexed by property name');
        $this->assertInstanceOf('SoftwarePunt\\Instarecord\\Database\\Column', $columns['userName'], 'Columns list should contain actual Column objects only');
    }

    /**
     * @depends testExtractsIndexedColumnList
     */
    public function testMemoryCachesColumnList()
    {
        $table = new Table('SoftwarePunt\\Instarecord\\Tests\\Samples\\TestUser');
        $columns = $table->getColumns();
        $columns2 = $table->getColumns();

        $this->assertSame($columns, $columns2, 'Column list should only be calculated once and cached in memory after');
    }
}