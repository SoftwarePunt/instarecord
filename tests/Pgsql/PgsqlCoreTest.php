<?php

namespace SoftwarePunt\Instarecord\Tests\Pgsql;

class PgsqlCoreTest extends PgsqlTestCase
{
    public function testPgsqlConnection()
    {
        if ($skipReason = $this->getPgsqlUnavailableReason()) {
            $this->markTestSkipped($skipReason);
            return;
        }

        $this->expectNotToPerformAssertions();

        $connection = $this->createConnection();
        $connection->open();
    }
}