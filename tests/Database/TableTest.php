<?php

namespace Softwarepunt\Instarecord\Tests\Database;

use PHPUnit\Framework\TestCase;
use Softwarepunt\Instarecord\Database\Table;

class TableTest extends TestCase
{
    public function testDefaultTableNameGeneration()
    {
        $this->assertEquals("users", Table::getDefaultTableName("User"));
        $this->assertEquals("my_table_classes", Table::getDefaultTableName("MyTableClass"));
        $this->assertEquals("my_table_classes", Table::getDefaultTableName("myTableClass"));
        $this->assertEquals("my_table_classes", Table::getDefaultTableName("My\\Qualified\\Namespaced\\MyTableClass"));
    }

    public function testConstructorErrorsOnBadClassName()
    {
        $this->expectException("Softwarepunt\Instarecord\Config\ConfigException");
        $this->expectExceptionMessage("invalid class");

        $table = new Table('bogus\\class\\not\\real');
    }

    public function testConstructorErrorsOnNonModelClassName()
    {
        $this->expectException("Softwarepunt\Instarecord\Config\ConfigException");
        $this->expectExceptionMessage("does not extend");

        $table = new Table('Softwarepunt\\Instarecord\\Tests\\Database\\TableTest');
    }

    public function testExtractsIndexedColumnList()
    {
        $table = new Table('Softwarepunt\\Instarecord\\Tests\\Samples\\User');
        $columns = $table->getColumns();

        $this->assertNotEmpty($columns, 'Expected a non-empty columns list');
        $this->assertArrayHasKey('userName', $columns, 'Columns list should be indexed by property name');
        $this->assertInstanceOf('Softwarepunt\\Instarecord\\Database\\Column', $columns['userName'], 'Columns list should contain actual Column objects only');
    }

    /**
     * @depends testExtractsIndexedColumnList
     */
    public function testMemoryCachesColumnList()
    {
        $table = new Table('Softwarepunt\\Instarecord\\Tests\\Samples\\User');
        $columns = $table->getColumns();
        $columns2 = $table->getColumns();

        $this->assertSame($columns, $columns2, 'Column list should only be calculated once and cached in memory after');
    }
}