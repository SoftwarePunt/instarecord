<?php

namespace Instasell\Instarecord\Tests;

use Instasell\Instarecord\Instarecord;
use Instasell\Instarecord\Tests\Testing\TestDatabaseConfig;
use PHPUnit\Framework\TestCase;

class InstarecordTest extends TestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testGetConfig()
    {
        $configObject = Instarecord::config();
        $this->assertInstanceOf('Instasell\Instarecord\Config\DatabaseConfig', $configObject, 'A default config object should be created and returned');
    }

    /**
     * @runInSeparateProcess 
     */
    public function testReplaceConfig()
    {
        $configObjectDefault = Instarecord::config();
        $myConfigObject = new TestDatabaseConfig();
        $configObjectNow = Instarecord::config($myConfigObject);
        
        $this->assertSame($myConfigObject, $configObjectNow, 'Passing config object to config() should cause original config to be replaced');
        $this->assertNotSame($configObjectNow, $configObjectDefault, 'Passing config object to config() should cause original config to be replaced');
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetConnection()
    {
        $connectionObject = Instarecord::connection();
        $this->assertInstanceOf('Instasell\Instarecord\Database\Connection', $connectionObject, 'A default connection object (based on a default config object) should be created and returned');
        $this->assertFalse($connectionObject->isOpen(), 'New connection should be closed by default');
    }

    /**
     * @runInSeparateProcess 
     */
    public function testReopenConnection()
    {
        $connectionObject = Instarecord::connection();
        $connectionObject2 = Instarecord::connection();
        
        $this->assertSame($connectionObject, $connectionObject2, 'Calling Instarecord::connection() twice should return the same, identical objects');
        
        $connectionObject3 = Instarecord::connection(true);
        
        $this->assertNotSame($connectionObject3, $connectionObject, 'Reopen should result in a new connection object');
        $this->assertNotSame($connectionObject3, $connectionObject2, 'Reopen should result in a new connection object');
    }

    /**
     * @runInSeparateProcess 
     */
    public function testCreateQuery()
    {
        $query = Instarecord::query();
        $this->assertInstanceOf('Instasell\Instarecord\Database\Query', $query, 'A new query object (based on a default connection and config object) should be created and returned');
    }
}