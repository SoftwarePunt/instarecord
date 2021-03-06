<?php

namespace SoftwarePunt\Instarecord\Tests\Adapters;

use PHPUnit\Framework\TestCase;
use SoftwarePunt\Instarecord\Adapters\MySqlAdapter;
use SoftwarePunt\Instarecord\Config\DatabaseConfig;
use SoftwarePunt\Instarecord\DatabaseAdapter;

class MysqlAdapterTest extends TestCase
{
    public function testGenerateDsn()
    {
        $adapter = new MySqlAdapter();
        
        $config = new DatabaseConfig();
        $config->adapter = DatabaseAdapter::MYSQL;
        $config->host = '1.2.3.4';
        $config->port = null;
        $config->database = 'dbname123';
        $config->charset = 'utf16';
        
        $this->assertEquals("mysql:host=1.2.3.4;dbname=dbname123;charset=utf16", $adapter->createDsn($config));
    }

    /**
     * @depends testGenerateDsn
     */
    public function testParseDsn()
    {
        $adapter = new MySqlAdapter();

        $config = new DatabaseConfig();
        $config->adapter = DatabaseAdapter::MYSQL;
        $config->host = '1.2.3.4';
        $config->port = null;
        $config->database = 'dbname123';
        $config->charset = 'utf16';
        $config->port = 1337;

        $dsnGenerated = $adapter->createDsn($config);
        $configParsed = $adapter->parseDsn($dsnGenerated);

        $this->assertEquals("mysql:host=1.2.3.4;port=1337;dbname=dbname123;charset=utf16", $dsnGenerated);
        $this->assertNotSame($config, $configParsed);
        $this->assertEquals($config, $configParsed);
    }

    /**
     * @depends testGenerateDsn
     */
    public function testParseDsnWithUnixSocket()
    {
        $adapter = new MySqlAdapter();

        $config = new DatabaseConfig();
        $config->adapter = DatabaseAdapter::MYSQL;
        $config->unix_socket = '/tmp/example/unix.sock';
        $config->host = null;
        $config->port = null;
        $config->database = 'dbname123';

        $dsnGenerated = $adapter->createDsn($config);
        $configParsed = $adapter->parseDsn($dsnGenerated);

        $this->assertEquals("mysql:unix_socket=/tmp/example/unix.sock;dbname=dbname123;charset=utf8mb4", $dsnGenerated);
        $this->assertNotSame($config, $configParsed);
        $this->assertEquals($config, $configParsed);
    }

    public function testGenerateDsnWithCustomPort()
    {
        $adapter = new MySqlAdapter();

        $config = new DatabaseConfig();
        $config->adapter = DatabaseAdapter::MYSQL;
        $config->host = '1.2.3.4';
        $config->port = 3307;

        $this->assertStringStartsWith("mysql:host=1.2.3.4;port=3307;", $adapter->createDsn($config));
    }

    public function testGenerateDsnWithUnixSocket()
    {
        $adapter = new MySqlAdapter();

        $config = new DatabaseConfig();
        $config->adapter = DatabaseAdapter::MYSQL;
        $config->host = '1.2.3.4';
        $config->port = 3307;
        $config->unix_socket = '/tmp/example/unix.sock';

        $generatedDsn = $adapter->createDsn($config);
        
        $this->assertStringStartsWith("mysql:unix_socket=/tmp/example/unix.sock;", $generatedDsn);
        $this->assertStringNotContainsString("host=", $generatedDsn);
        $this->assertStringNotContainsString("port=", $generatedDsn);
    }

    public function testTranslatesLocalhostToLocalIp()
    {
        $adapter = new MySqlAdapter();

        $config = new DatabaseConfig();
        $config->adapter = DatabaseAdapter::MYSQL;
        $config->host = 'localhost';
        $config->port = null;
        $config->database = 'dbname123';
        $config->charset = 'utf16';

        $this->assertEquals("mysql:host=127.0.0.1;dbname=dbname123;charset=utf16", $adapter->createDsn($config));
    }
}
