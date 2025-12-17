<?php

namespace SoftwarePunt\Instarecord\Tests\Database;

use Minime\Annotations\AnnotationsBag;
use PHPUnit\Framework\TestCase;
use SoftwarePunt\Instarecord\Database\Column;
use SoftwarePunt\Instarecord\Database\Table;
use SoftwarePunt\Instarecord\Instarecord;
use SoftwarePunt\Instarecord\Tests\Samples\TestDummySerializableType;
use Softwarepunt\Instarecord\Tests\Samples\TestBackedEnum;

class DataFormattingTest extends TestCase
{
    private function _createTestColumn(array $opts)
    {
        $table = new Table('SoftwarePunt\\Instarecord\\Tests\\Samples\\TestUser');
        $column = new Column($table, 'testColumn', null);

        if (!empty($opts['var'])) {
            $rfType = new \ReflectionProperty($column, "dataType");
            $rfType->setAccessible(true);
            $rfType->setValue($column, $opts['var']);
        }

        if (!empty($opts['reftype'])) {
            $rfType = new \ReflectionProperty($column, "referenceType");
            $rfType->setAccessible(true);
            $rfType->setValue($column, $opts['reftype']);
        }

        if (!empty($opts['reftype'])) {
            $rfType = new \ReflectionProperty($column, "referenceType");
            $rfType->setAccessible(true);
            $rfType->setValue($column, $opts['reftype']);
        }

        if (!empty($opts['enumtype'])) {
            $rfType = new \ReflectionProperty($column, "reflectionEnum");
            $rfType->setAccessible(true);
            $rfType->setValue($column, new \ReflectionEnum($opts['enumtype']));
        }

        if (!empty($opts['nullable'])) {
            $rfType = new \ReflectionProperty($column, "isNullable");
            $rfType->setAccessible(true);
            $rfType->setValue($column, true);
        }

        return $column;
    }

    public function testDecimals()
    {
        $column = $this->_createTestColumn(['var' => 'decimal']);

        $inputValue = '12.32';

        $this->assertSame(4, $column->getDecimals());
        $this->assertSame("12.3200", $column->formatDatabaseValue($inputValue));
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
        $column = $this->_createTestColumn(['var' => Column::TYPE_DATE_TIME]);
        $testDateTimeStr = '2013-02-01 11:22:33';

        $parsedDateTime = $column->parseDatabaseValue($testDateTimeStr);
        
        $this->assertInstanceOf('\DateTime', $parsedDateTime, 'DateTime value should be parsed into DateTime object');

        /**
         * @var \DateTime $parsedDateTime
         */

        $this->assertEquals($testDateTimeStr, $parsedDateTime->format(Column::DATE_TIME_FORMAT), 'DateTime parsing should maintain correct value');
        $this->assertEquals(Instarecord::config()->timezone, $parsedDateTime->getTimezone()->getName(), 'DateTime parsing should return correct timezone');
    }

    /**
     * @runInSeparateProcess
     */
    public function testDateTimeParseWithAltTimezone()
    {
        $customTzName = 'Europe/Amsterdam';
        Instarecord::config()->timezone = $customTzName;

        $column = $this->_createTestColumn(['var' => Column::TYPE_DATE_TIME]);
        $parsedDateTime = $column->parseDatabaseValue('2013-02-01 11:22:33');

        $this->assertEquals(
            $customTzName,
            $parsedDateTime->getTimezone()->getName(),
            'DateTime parsing should return correct timezone'
        );
    }

    public function testDateTimeParseFailuresDoesNotResultInFalse()
    {
        $column = $this->_createTestColumn(['var' => Column::TYPE_DATE_TIME]);
        $testDateTimeStr = 'pqadfgrashijklmaqxynostuvz';
        $parsedDateTime = $column->parseDatabaseValue($testDateTimeStr);

        $this->assertNotSame(false, $parsedDateTime, 'DateTime parse failures should never result in FALSE');
    }

    public function testDateTimeParseWithTimeOnly()
    {
        $column = $this->_createTestColumn(['var' => Column::TYPE_DATE_TIME]);
        $testDateTimeStr = '11:22:33';
        $parsedDateTime = $column->parseDatabaseValue($testDateTimeStr);

        $expected = date('Y-m-d') . ' ' . $testDateTimeStr;

        $this->assertInstanceOf('\DateTime', $parsedDateTime, 'DateTime-timeonly value should be parsed into DateTime object');
        $this->assertEquals($expected, $parsedDateTime->format(Column::DATE_TIME_FORMAT), 'DateTime-timeonly parsing should maintain correct value');
    }

    public function testDateTimeParsesWhenAlsoNullable()
    {
        $column = $this->_createTestColumn(['var' => Column::TYPE_DATE_TIME, 'nullable' => true]);
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

    public function testFormatsNullableIntegerDataTypes()
    {
        $column = $this->_createTestColumn(['var' => Column::TYPE_INTEGER, 'nullable' => true]);

        $this->assertSame(null, $column->formatDatabaseValue(null));
        $this->assertSame(null, $column->formatDatabaseValue(''));
        $this->assertSame("0", $column->formatDatabaseValue(0));
        $this->assertSame("1", $column->formatDatabaseValue('1'));
        $this->assertSame("12", $column->formatDatabaseValue('12,35'));
    }

    public function testFormatsNonNullableIntegerDataTypes()
    {
        $column = $this->_createTestColumn(['var' => Column::TYPE_INTEGER]);

        $this->assertSame("0", $column->formatDatabaseValue(null));
        $this->assertSame("0", $column->formatDatabaseValue(''));
        $this->assertSame("0", $column->formatDatabaseValue(0));
        $this->assertSame("1", $column->formatDatabaseValue('1'));
    }

    public function testSupportsNullableBooleanFormatting()
    {
        $column = $this->_createTestColumn(['var' => Column::TYPE_BOOLEAN, 'nullable' => true]);

        $this->assertSame("0", $column->formatDatabaseValue(0));
        $this->assertSame("1", $column->formatDatabaseValue(1));
        $this->assertSame(null, $column->formatDatabaseValue(null));

        // This is a bit of an edge case, but I came up with it on SO, and it's kinda useful sometimes:
        // https://stackoverflow.com/a/45087858/1410310
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

    public function testParsesIntegerValues()
    {
        $column = $this->_createTestColumn(['var' => Column::TYPE_INTEGER]);

        $this->assertEquals(-1, $column->parseDatabaseValue('-1'));
        $this->assertEquals(0, $column->parseDatabaseValue('0'));
        $this->assertEquals(1, $column->parseDatabaseValue('1'));
        $this->assertEquals(0, $column->parseDatabaseValue('txt'));
        $this->assertEquals(3, $column->parseDatabaseValue('3,5'));
        $this->assertEquals(12, $column->parseDatabaseValue(12.33));
        $this->assertEquals(1, $column->parseDatabaseValue(true));
    }

    public function testParsesDecimalValues()
    {
        $column = $this->_createTestColumn(['var' => Column::TYPE_DECIMAL]);

        $this->assertEquals(0, $column->parseDatabaseValue('0'));
        $this->assertEquals(-1.33, $column->parseDatabaseValue('-1.33'));
        $this->assertEquals(12.345, $column->parseDatabaseValue('12.345'));
        $this->assertEquals(0, $column->parseDatabaseValue('txt'));
    }

    public function testParsesNullableDecimalValues()
    {
        $column = $this->_createTestColumn(['var' => Column::TYPE_DECIMAL, 'nullable' => true]);

        $this->assertEquals(null, $column->parseDatabaseValue(null));
        $this->assertEquals(0, $column->parseDatabaseValue('0'));
    }

    public function testIDatabaseSerializable()
    {
        $column = $this->_createTestColumn([
            'var' => Column::TYPE_SERIALIZED_OBJECT,
            'nullable' => true,
            'reftype' => new TestDummySerializableType()
        ]);

        $this->assertSame(null, $column->parseDatabaseValue(null));
        $this->assertEquals($obj = new TestDummySerializableType("test 123"), $column->parseDatabaseValue("test 123"));
    }

    public function testEnum()
    {
        $column = $this->_createTestColumn([
            'var' => Column::TYPE_ENUM,
            'nullable' => true,
            'enumtype' => TestBackedEnum::class
        ]);

        $this->assertSame(null, $column->parseDatabaseValue(null));
        $this->assertSame("three", $column->formatDatabaseValue(TestBackedEnum::Three));
        $this->assertEquals(TestBackedEnum::Two, $column->parseDatabaseValue("two"));
    }
}
