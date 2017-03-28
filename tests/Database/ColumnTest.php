<?php

namespace Instasell\Instarecord\Tests\Database;

use Instasell\Instarecord\Database\Column;
use Instasell\Instarecord\Database\Table;
use Minime\Annotations\AnnotationsBag;
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

    public function testCustomColumnNames()
    {
        $table = new Table('Instasell\\Instarecord\\Tests\\Samples\\User');
        $annotationBag = new AnnotationsBag(['column' => 'custom_column_name']);
        $column = new Column($table, 'myPropName', $annotationBag);

        $this->assertEquals('custom_column_name', $column->getColumnName(), "A custom @columnn annotation should override the default column name");
    }
}