<?php

namespace Instasell\Instarecord\Tests\Testing;

use Instasell\Instarecord\Config\DatabaseConfig;
use Instasell\Instarecord\DatabaseAdapter;

class TestDatabaseConfig extends DatabaseConfig
{
    public function __construct()
    {
        $this->adapter = DatabaseAdapter::MYSQL;
        $this->username = TEST_USER_NAME;
        $this->password = TEST_PASSWORD;
        $this->database = TEST_DATABASE_NAME;
    }
}