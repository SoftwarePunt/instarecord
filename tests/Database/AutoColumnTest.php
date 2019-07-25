<?php

namespace Instasell\Instarecord\Tests\Database;

use Instasell\Instarecord\Database\Column;
use Instasell\Instarecord\Database\Table;
use Instasell\Instarecord\Instarecord;
use Instasell\Instarecord\Tests\Samples\UserAutoTest;
use Instasell\Instarecord\Tests\Testing\TestDatabaseConfig;
use PHPUnit\Framework\TestCase;

class AutoColumnTest extends TestCase
{
    /**
     * Asserts that the "@auto" annotation is correctly read and applied to the Column instance.
     */
    public function testAutoModeAnnotation()
    {
        $table = new Table('Instasell\\Instarecord\\Tests\\Samples\\AutoColumnTest');

        $this->assertEquals("created", $table->getColumnByPropertyName("createdAt")->getAutoMode());
        $this->assertEquals("modified", $table->getColumnByPropertyName("modifiedAt")->getAutoMode());
        $this->assertNull($table->getColumnByPropertyName("id")->getAutoMode());
    }

    /**
     * Asserts that the "@auto" annotation automatically implies the appropriate data type (e.g. DateTime), despite
     * the "@type" being omitted for these columns.
     */
    public function testAutoModeImpliesDataType()
    {
        $table = new Table('Instasell\\Instarecord\\Tests\\Samples\\AutoColumnTest');

        $this->assertEquals(Column::TYPE_DATE_TIME, $table->getColumnByPropertyName("createdAt")->getType());
        $this->assertEquals(Column::TYPE_DATE_TIME, $table->getColumnByPropertyName("modifiedAt")->getType());
    }

    /**
     * Asserts that the "@auto" annotation requires a supported value.
     */
    public function testAutoModeThrowsOnInvalidAutoAnnotation()
    {
        $this->expectException("Instasell\Instarecord\Database\ColumnDefinitionException");
        $this->expectExceptionMessage("invalid @auto value");

        $table = new Table('Instasell\\Instarecord\\Tests\\Samples\\AutoColumnTestBadAuto');
        $table->getColumnByPropertyName("createdAt");
    }

    /**
     * Asserts that the "@auto" annotation does not accept incompatible column types.
     */
    public function testAutoModeThrowsOnIncompatibleTypes()
    {
        $this->expectException("Instasell\Instarecord\Database\ColumnDefinitionException");
        $this->expectExceptionMessage("@auto mode of `created` expects a @type of `datetime`");

        $table = new Table('Instasell\\Instarecord\\Tests\\Samples\\AutoColumnTestBadType');
        $table->getColumnByPropertyName("createdAt");
    }

    /**
     * Asserts that "@auto created" sets the value on new model insert.
     */
    public function testAutoCreateOnNewRecord()
    {
        Instarecord::config(new TestDatabaseConfig());

        $uat = new UserAutoTest();

        try {
            $uat->userName = "Newly Created";

            $this->assertEmpty($uat->createdAt);
            $this->assertTrue($uat->save());
            $this->assertNotEmpty($uat->createdAt);
        } finally {
            @$uat->delete();
        }
    }

    /**
     * Asserts that "@auto created" does not set the value on new model insert, if it already has a value.
     */
    public function testAutoCreateOnNewRecordLeavesExplicitValue()
    {
        Instarecord::config(new TestDatabaseConfig());

        $uat = new UserAutoTest();

        try {
            $testDt = new \DateTime("1970-01-02 03:04:05");

            $uat->userName = "Newly Created";
            $uat->createdAt = $testDt;

            $this->assertTrue($uat->save());
            $this->assertSame($testDt, $uat->createdAt);
        } finally {
            @$uat->delete();
        }
    }

    /**
     * Asserts that "@auto modified" sets the value on new model insert.
     */
    public function testAutoModifiedOnNewRecord()
    {
        Instarecord::config(new TestDatabaseConfig());

        $uat = new UserAutoTest();

        try {
            $uat->userName = "Newly Created";

            $this->assertEmpty($uat->modifiedAt);
            $this->assertTrue($uat->save());
            $this->assertNotEmpty($uat->modifiedAt);
        } finally {
            @$uat->delete();
        }
    }

    /**
     * Asserts that "@auto modified" sets the value on new model insert.
     */
    public function testAutoModifiedOnUpdatedRecord()
    {
        Instarecord::config(new TestDatabaseConfig());

        $uat = new UserAutoTest();

        try {
            $uat->userName = "Newly Created";

            $this->assertTrue($uat->save());
            $this->assertNotEmpty($uat->modifiedAt);

            $modifiedOnSave = $uat->modifiedAt;

            $uat->userName = "Newly Modified";

            sleep(1);

            $this->assertTrue($uat->save());
            $this->assertGreaterThan($modifiedOnSave->getTimestamp(), $uat->modifiedAt->getTimestamp());
        } finally {
            @$uat->delete();
        }
    }
}