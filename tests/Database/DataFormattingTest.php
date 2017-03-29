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
        $column = $this->_createTestColumn(['var' => '\DateTime']);
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
}