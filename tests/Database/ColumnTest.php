<?php

namespace Instasell\Instarecord\Tests\Database;

use Instasell\Instarecord\Database\Column;
use Instasell\Instarecord\Database\Table;
use Instasell\Instarecord\Reflection\ReflectionModel;
use Minime\Annotations\AnnotationsBag;
use PHPUnit\Framework\TestCase;

class ColumnTest extends TestCase
{
    public function testDetermineDefaultColumnName()
    {
        $this->assertEquals("my_simple_name",       Column::getDefaultColumnName("my_simple_name"));
        $this->assertEquals("my_simple_name",       Column::getDefaultColumnName("mySimpleName"));
        $this->assertEquals("my_simple_name",       Column::getDefaultColumnName("MySimpleName"));
        $this->assertEquals("mysimplename",         Column::getDefaultColumnName("mysimplename"));
        $this->assertEquals("some4_numbers234",     Column::getDefaultColumnName("Some4Numbers234"));
        $this->assertEquals("test123_string",       Column::getDefaultColumnName("TEST123String"));
    }

    public function testRespectsCustomColumnNames()
    {
        $table = new Table('Instasell\\Instarecord\\Tests\\Samples\\User');

        $rfProp = ReflectionModel::fromClassName('Instasell\\Instarecord\\Tests\\Samples\\User')
            ->getReflectionProperties()['id'];

        $annotationBag = new AnnotationsBag(['column' => 'custom_column_name']);

        $column = new Column($table, 'id', $rfProp, $annotationBag);

        $this->assertEquals('custom_column_name', $column->getColumnName(), "A custom @columnn annotation should override the default column name");
    }

    public function testGeneratesDefaultColumnNames()
    {
        $table = new Table('Instasell\\Instarecord\\Tests\\Samples\\User');
        $annotationBag = new AnnotationsBag([]);
        $column = new Column($table, 'myPropName', null, $annotationBag);

        $this->assertEquals('my_prop_name', $column->getColumnName(), "If no custom @columnn annotation is set, default column conventions should be assumed");
    }
    
    public function testUnderstandsNullables()
    {
        $table = new Table('Instasell\\Instarecord\\Tests\\Samples\\NullableTest');
        
        $this->assertFalse($table->getColumnByPropertyName('stringNonNullable')->getIsNullable());
        $this->assertTrue($table->getColumnByPropertyName('stringNullableThroughType')->getIsNullable());
        $this->assertTrue($table->getColumnByPropertyName('stringNullableThroughAnnotation')->getIsNullable());
    }

    public function testReadsDefaultValues()
    {
        $table = new Table('Instasell\\Instarecord\\Tests\\Samples\\DefaultsTest');

        $this->assertEquals("hello1", $table->getColumnByPropertyName('strNullableWithDefault')->getDefaultValue());
        $this->assertEquals("hello2", $table->getColumnByPropertyName('strNonNullableWithDefault')->getDefaultValue());
        $this->assertEquals(null, $table->getColumnByPropertyName('strDefaultNullValue')->getDefaultValue());
    }
}