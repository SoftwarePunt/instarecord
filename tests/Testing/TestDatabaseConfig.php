<?php

namespace Softwarepunt\Instarecord\Tests\Testing;

use Softwarepunt\Instarecord\Config\DatabaseConfig;

class TestDatabaseConfig extends DatabaseConfig
{
    public function __construct()
    {
        $this->host = TEST_DATABASE_HOST;
        $this->port = TEST_DATABASE_PORT;
        $this->username = TEST_USER_NAME;
        $this->password = TEST_PASSWORD;
        $this->database = TEST_DATABASE_NAME;
        $this->charset = "utf8mb4";
    }
}