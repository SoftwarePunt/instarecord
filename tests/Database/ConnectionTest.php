<?php

namespace SoftwarePunt\Instarecord\Tests\Database;

use PHPUnit\Framework\TestCase;
use SoftwarePunt\Instarecord\Config\DatabaseConfig;
use SoftwarePunt\Instarecord\Database\Connection;
use SoftwarePunt\Instarecord\DatabaseAdapter;
use SoftwarePunt\Instarecord\Tests\Testing\TestDatabaseConfig;

;

class ConnectionTest extends TestCase
{
    public function testConnectionIsInitiallyClosed()
    {
        $connection = new Connection(new DatabaseConfig());
        $this->assertFalse($connection->isOpen(), 'New connections should not open initially');
    }

    public function testConnectionDsnGenerate()
    {
        $config = new DatabaseConfig();
        $config->adapter = DatabaseAdapter::MySql;
        $config->host = "testhost";
        $config->port = 1234;
        $config->username = "testuser";
        $config->password = "testpwd";
        $config->database = "testdb";
        $config->charset = "utf8mb4";

        $expectedDsn = "mysql:host=testhost;port=1234;dbname=testdb;charset=utf8mb4";
        $actualDsn = (new Connection($config))->generateDsn();

        $this->assertEquals($expectedDsn, $actualDsn);
    }
    
    public function testOpenCloseAndReopen()
    {
        $config = new DatabaseConfig();
        $config->adapter = DatabaseAdapter::MySql;
        $config->host = TEST_DATABASE_HOST;
        $config->port = TEST_DATABASE_PORT;
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

    public function testOpenThrowsExceptionForInvalidCredentials()
    {
        $this->expectException("SoftwarePunt\Instarecord\Database\DatabaseException");
        $this->expectExceptionMessage("Database connection failed");

        $config = new DatabaseConfig();
        $config->username = 'badname_notreal_noway';
        
        $connection = new Connection($config);
        $connection->open();
    }

    /**
     * @depends testConnectionIsInitiallyClosed
     */
    public function testExecuteStatement()
    {
        $config = new TestDatabaseConfig();
        $connection = new Connection($config);
        
        $statement = $connection->executeStatement("SELECT * FROM users;");
        
        $this->assertTrue($connection->isOpen(), 'Creating a statement should open the connection');
        $this->assertInstanceOf('\PDOStatement', $statement, 'Expected a raw PDOStatement as a result');
        
        $resultSet = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $this->assertIsArray($resultSet, 'Expected assoc array as result with no errors');
    }
}
