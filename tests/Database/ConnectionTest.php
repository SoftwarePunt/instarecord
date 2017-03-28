<?php

namespace Instasell\Instarecord\Tests\Database;

use Instasell\Instarecord\Config\DatabaseConfig;
use Instasell\Instarecord\Database\Connection;
use Instasell\Instarecord\DatabaseAdapter;
use PHPUnit\Framework\TestCase;

class ConnectionTest extends TestCase
{
    public function testOpenCloseAndReopen()
    {
        $config = new DatabaseConfig();
        $config->adapter = DatabaseAdapter::MYSQL;
        $config->username = TEST_USER_NAME;
        $config->password = TEST_PASSWORD;
        $config->database = TEST_DATABASE_NAME;
        
        $connection = new Connection($config);
        
        $this->assertFalse($connection->isOpen(), 'New connections should not open initially');
        
        $connection->open();
        
        $this->assertTrue($connection->isOpen(), 'Database connection should succeed');
        
        $connection->close();

        $this->assertFalse($connection->isOpen(), 'Database connection close should succeed');
        
        $connection->open();

        $this->assertTrue($connection->isOpen(), 'Database connection reopen should succeed');
    }
}
