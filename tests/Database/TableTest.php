<?php

namespace Instasell\Instarecord\Tests\Database;

use Instasell\Instarecord\Database\Table;
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

    /**
     * @expectedException Instasell\Instarecord\Config\ConfigException
     * @expectedExceptionMessage invalid class
     */
    public function testConstructorErrorsOnBadClassName()
    {
        $table = new Table('bogus\\class\\not\\real');
    }

    /**
     * @expectedException Instasell\Instarecord\Config\ConfigException
     * @expectedExceptionMessage does not extend
     */
    public function testConstructorErrorsOnNonModelClassName()
    {
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

    /**
     * @depends testExtractsIndexedColumnList
     */
    public function testExtractsAnnotationInfoIntoColumns()
    {
        $table = new Table('Instasell\\Instarecord\\Tests\\Samples\\User');
        $columns = $table->getColumns();
        $userNameColumn = $columns['userName'];

        // We're testing this on a private member because the API purposely doesn't expose the AnnotationBag
        // Yet we want to test whether annotations are being parsed & passed correctly, somehow
        // This isn't the prettiest solution but I'll sleep better at night knowing it's here
        Assert::assertAttributeContains('myCustomAnnotation', 'annotations', $userNameColumn);
        Assert::assertAttributeContains('string', 'annotations', $userNameColumn);
    }
}