<?php

namespace Instasell\Instarecord\Tests\Adapters;

use Instasell\Instarecord\Adapters\MySqlAdapter;
use Instasell\Instarecord\Config\DatabaseConfig;
use Instasell\Instarecord\DatabaseAdapter;
use PHPUnit\Framework\TestCase;

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
        $this->assertNotContains("host=", $generatedDsn);
        $this->assertNotContains("port=", $generatedDsn);
        
    }
}