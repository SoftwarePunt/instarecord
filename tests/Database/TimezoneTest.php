<?php

namespace Softwarepunt\Instarecord\Tests\Database;

use PHPUnit\Framework\TestCase;
use SoftwarePunt\Instarecord\Instarecord;
use SoftwarePunt\Instarecord\Tests\Samples\User;
use SoftwarePunt\Instarecord\Tests\Testing\TestDatabaseConfig;

class TimezoneTest extends TestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testTimezoneWriteConsistency()
    {
        if (!date_default_timezone_set("Europe/Amsterdam"))
            $this->markTestSkipped('Failed to set default PHP timezone');

        Instarecord::config(new TestDatabaseConfig());
        Instarecord::config()->timezone = "Europe/Amsterdam";

        $jan1st = new \DateTime("2023-01-01 00:00:00");

        $user = new User();
        $user->userName = "TimeZoneTest";
        $user->joinDate = $jan1st;
        $user->save();

        $userRefetch = User::fetch($user->id);

        $this->assertSame(
            "2023-01-01 00:00:00",
            $userRefetch->joinDate->format('Y-m-d H:i:s')
        );
    }

    /**
     * @depends testTimezoneWriteConsistency
     * @runInSeparateProcess
     */
    public function testTimezoneQueryConsistency()
    {
        if (!date_default_timezone_set("Europe/Amsterdam"))
            $this->markTestSkipped('Failed to set default PHP timezone');

        Instarecord::config(new TestDatabaseConfig());
        Instarecord::config()->timezone = "Europe/Amsterdam";

        $jan1st = new \DateTime("2023-01-01 00:00:00");

        $query = User::query()->where('join_date = ?', $jan1st);

        $queryString = $query->createStatementText();
        $queryParams = $query->getBoundParametersForGeneratedStatement();

        $this->assertEquals('SELECT * FROM users WHERE (join_date = ?);', $queryString);
        $this->assertEquals('2023-01-01 00:00:00', $queryParams[0],
            "Query date/time should automatically adjust to database timezone");
    }

    /**
     * @depends testTimezoneWriteConsistency
     * @runInSeparateProcess
     */
    public function testTimezoneQueryInconsistency_Up()
    {
        if (!date_default_timezone_set("UTC"))
            $this->markTestSkipped('Failed to set default PHP timezone');

        Instarecord::config(new TestDatabaseConfig());
        Instarecord::config()->timezone = "Europe/Amsterdam";

        $jan1st = new \DateTime("2023-01-01 00:00:00");

        $query = User::query()->where('join_date = ?', $jan1st);

        $queryString = $query->createStatementText();
        $queryParams = $query->getBoundParametersForGeneratedStatement();

        $this->assertEquals('SELECT * FROM users WHERE (join_date = ?);', $queryString);
        $this->assertEquals('2023-01-01 01:00:00', $queryParams[0],
            "Query date/time should automatically adjust to database timezone");
    }

    /**
     * @depends testTimezoneWriteConsistency
     * @runInSeparateProcess
     */
    public function testTimezoneQueryInconsistency_Down()
    {
        if (!date_default_timezone_set("Europe/Amsterdam"))
            $this->markTestSkipped('Failed to set default PHP timezone');

        Instarecord::config(new TestDatabaseConfig());
        Instarecord::config()->timezone = "UTC";

        $jan1st = new \DateTime("2023-01-01 00:00:00");

        $query = User::query()->where('join_date = ?', $jan1st);

        $queryString = $query->createStatementText();
        $queryParams = $query->getBoundParametersForGeneratedStatement();

        $this->assertEquals('SELECT * FROM users WHERE (join_date = ?);', $queryString);
        $this->assertEquals('2022-12-31 23:00:00', $queryParams[0],
            "Query date/time should automatically adjust to database timezone");
    }
}