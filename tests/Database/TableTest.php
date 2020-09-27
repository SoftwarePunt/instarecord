<?php

namespace Instasell\Instarecord\Tests\Database;

use Instasell\Instarecord\Database\Table;
use Instasell\Instarecord\Tests\Samples\CustomTableNameModel;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

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
        $this->expectException("Instasell\Instarecord\Config\ConfigException");
        $this->expectExceptionMessage("invalid class");

        $table = new Table('bogus\\class\\not\\real');
    }

    public function testConstructorErrorsOnNonModelClassName()
    {
        $this->expectException("Instasell\Instarecord\Config\ConfigException");
        $this->expectExceptionMessage("does not extend");

        $table = new Table('Instasell\\Instarecord\\Tests\\Database\\TableTest');
    }

    public function testExtractsIndexedColumnList()
    {
        $table = new Table('Instasell\\Instarecord\\Tests\\Samples\\User');
        $columns = $table->getColumns();

        $this->assertNotEmpty($columns, 'Expected a non-empty columns list');
        $this->assertArrayHasKey('userName', $columns, 'Columns list should be indexed by property name');
        $this->assertInstanceOf('Instasell\\Instarecord\\Database\\Column', $columns['userName'], 'Columns list should contain actual Column objects only');
    }

    /**
     * @depends testExtractsIndexedColumnList
     */
    public function testMemoryCachesColumnList()
    {
        $table = new Table('Instasell\\Instarecord\\Tests\\Samples\\User');
        $columns = $table->getColumns();
        $columns2 = $table->getColumns();

        $this->assertSame($columns, $columns2, 'Column list should only be calculated once and cached in memory after');
    }
}