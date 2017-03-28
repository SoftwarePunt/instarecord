<?php

namespace Instasell\Instarecord\Tests\Database;

use Instasell\Instarecord\Config\DatabaseConfig;
use Instasell\Instarecord\Database\Connection;;
use Instasell\Instarecord\DatabaseAdapter;
use PHPUnit\Framework\TestCase;

class ConnectionTest extends TestCase
{
    public function testConnectionIsInitiallyClosed()
    {
        $connection = new Connection(new DatabaseConfig());
        $this->assertFalse($connection->isOpen(), 'New connections should not open initially');
    }
    
    public function testOpenCloseAndReopen()
    {
        $config = new DatabaseConfig();
        $config->adapter = DatabaseAdapter::MYSQL;
        $config->username = TEST_USER_NAME;
        $config->password = TEST_PASSWORD;
        $config->database = TEST_DATABASE_NAME;
        
        $connection = new Connection($config);
        
        $connection->open();
        
        $this->assertTrue($connection->isOpen(), 'Database connection should succeed');
        
        $connection->close();

        $this->assertFalse($connection->isOpen(), 'Database connection close should succeed');
        
        $connection->open();

        $this->assertTrue($connection->isOpen(), 'Database connection reopen should succeed');
    }

    /**
     * @expectedException Instasell\Instarecord\Database\DatabaseException
     * @expectedExceptionMessage Database connection failed
     */
    public function testOpenThrowsExceptionForInvalidCredentials()
    {
        $config = new DatabaseConfig();
        $config->username = 'badname_notreal_noway';
        
        $connection = new Connection($config);
        $connection->open();
    }

    /**
     * @depends testConnectionIsInitiallyClosed
     */
    public function testCreateStatement()
    {
        $config = new DatabaseConfig();
        $config->adapter = DatabaseAdapter::MYSQL;
        $config->username = TEST_USER_NAME;
        $config->password = TEST_PASSWORD;
        $config->database = TEST_DATABASE_NAME;

        $connection = new Connection($config);
        
        $statement = $connection->createStatement("SELECT * FROM users;");
        
        $this->assertTrue($connection->isOpen(), 'Creating a statement should open the connection');
        $this->assertInstanceOf('\PDOStatement', $statement, 'Expected a raw PDOStatement');
    }
}
