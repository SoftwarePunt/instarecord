<?php

namespace Instasell\Instarecord\Tests\Database;

use Instasell\Instarecord\Database\Column;
use Instasell\Instarecord\Database\Table;
use Minime\Annotations\AnnotationsBag;
use PHPUnit\Framework\TestCase;

class DataFormattingTest extends TestCase
{
    private function _createTestColumn(array $annotations)
    {
        $annotationBag = new AnnotationsBag($annotations);
        $table = new Table('Instasell\\Instarecord\\Tests\\Samples\\User');
        $column = new Column($table, 'testColumn', $annotationBag);
        return $column;
    }
    
    public function testDateTimeFormat()
    {
        $column = $this->_createTestColumn(['var' => 'anything']);
        $testDateTimeStr = '2013-02-01 11:22:33';
        $testDateTime = new \DateTime($testDateTimeStr);
        
        $this->assertEquals('blah blah', $column->formatDatabaseValue('blah blah'), 'Should not attempt to format non-DateTime values');
        $this->assertEquals($testDateTimeStr, $column->formatDatabaseValue($testDateTime), 'Should format date time objects correctly');
    }
    
    public function testDateTimeParse()
    {
        $column = $this->_createTestColumn(['var' => '\DateTime']);
        $testDateTimeStr = '2013-02-01 11:22:33';
        $parsedDateTime = $column->parseDatabaseValue($testDateTimeStr);
        
        $this->assertInstanceOf('\DateTime', $parsedDateTime, 'DateTime value should be parsed into DateTime object');
        $this->assertEquals($testDateTimeStr, $parsedDateTime->format(Column::DATE_TIME_FORMAT), 'DateTime parsing should maintain correct value');
    }

    public function testDateTimeParsesWhenAlsoNullable()
    {
        $column = $this->_createTestColumn(['var' => "\\DateTime|null"]);
        $testDateTimeStr = '2013-02-01 11:22:33';
        $parsedDateTime = $column->parseDatabaseValue($testDateTimeStr);

        $this->assertInstanceOf('\DateTime', $parsedDateTime, 'DateTime value should be parsed into DateTime object');
        $this->assertEquals($testDateTimeStr, $parsedDateTime->format(Column::DATE_TIME_FORMAT), 'DateTime parsing should maintain correct value');
    }

    public function testFormatRetainsNulls()
    {
        $column = $this->_createTestColumn(['var' => '\DateTime']);

        $this->assertEquals(null, $column->formatDatabaseValue(null), 'NULL should not be formatted; it should remain NULL');
    }

    public function testParseRetainsNulls()
    {
        $column = $this->_createTestColumn(['var' => '\DateTime']);

        $this->assertNull($column->parseDatabaseValue(null), 'NULL should not be parsed; it should remain NULL');
    }

    public function testFormatConvertsPureBooleans()
    {
        $column = $this->_createTestColumn(['var' => 'anything']);

        $this->assertEquals('1', $column->formatDatabaseValue(true), 'boolean(true) should be converted into int(1)');
        $this->assertEquals('0', $column->formatDatabaseValue(false), 'boolean(false) should be converted into int(1)');
    }

    public function testFormatsBooleanDataTypes()
    {
        $column = $this->_createTestColumn(['var' => 'bool']);

        $this->assertEquals('1', $column->formatDatabaseValue('bla'), 'string(bla) should be converted into int(1) for boolean data type');
        $this->assertEquals('0', $column->formatDatabaseValue('false'), 'string(false) should be converted into int(0) for boolean data type');
        $this->assertEquals('0', $column->formatDatabaseValue(0), 'int(0) should be retained as int(0) for boolean data type');
        $this->assertEquals('0', $column->formatDatabaseValue("000"), 'string("000") should be retained as int(0) for boolean data type');
    }

    public function testParsesBooleanValues()
    {
        $column = $this->_createTestColumn(['var' => 'bool']);

        $this->assertEquals(true, $column->parseDatabaseValue('1'));
        $this->assertEquals(true, $column->parseDatabaseValue('true'));
        $this->assertEquals(false, $column->parseDatabaseValue('0'));
        $this->assertEquals(false, $column->parseDatabaseValue('false'));
        $this->assertEquals(false, $column->parseDatabaseValue(null));
    }
}