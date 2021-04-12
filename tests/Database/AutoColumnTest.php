<?php

namespace SoftwarePunt\Instarecord\Tests\Database;

use PHPUnit\Framework\TestCase;
use SoftwarePunt\Instarecord\Database\Table;
use SoftwarePunt\Instarecord\Instarecord;
use SoftwarePunt\Instarecord\Tests\Samples\UserAutoTest;
use SoftwarePunt\Instarecord\Tests\Testing\TestDatabaseConfig;

class AutoColumnTest extends TestCase
{
    /**
     * Asserts that the auto mode is correctly determined.
     */
    public function testAutoModeDetermination()
    {
        $table = new Table('SoftwarePunt\\Instarecord\\Tests\\Samples\\AutoColumnTest');

        $this->assertEquals("created", $table->getColumnByPropertyName("createdAt")->getAutoMode());
        $this->assertEquals("modified", $table->getColumnByPropertyName("modifiedAt")->getAutoMode());
        $this->assertNull($table->getColumnByPropertyName("id")->getAutoMode());
    }

    /**
     * Asserts that the auto mode is not enabled for columns with incompatible types.
     */
    public function testAutoModeRequiresCompatibleType()
    {
        $table = new Table('SoftwarePunt\\Instarecord\\Tests\\Samples\\AutoColumnTestBadAuto');
        $this->assertNull($table->getColumnByPropertyName("createdAt")->getAutoMode());
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