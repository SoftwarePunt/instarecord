<?php

namespace Instasell\Instarecord\Tests\Database;

use Instasell\Instarecord\Config\DatabaseConfig;
use Instasell\Instarecord\Database\Connection;
use Instasell\Instarecord\Database\Query;
use Instasell\Instarecord\DatabaseAdapter;
use PHPUnit\Framework\TestCase;

class QueryTest extends TestCase
{
    public function testSimpleSelect()
    {
        $query = new Query(new Connection(new DatabaseConfig()));
        
        $queryString = $query->select('*')
            ->from('users')
            ->createStatementText();
        
        $this->assertEquals('SELECT * FROM users;', $queryString);
    }
    
    public function testSimpleDelete()
    {
        $query = new Query(new Connection(new DatabaseConfig()));

        $queryString = $query->delete()
            ->from('users')
            ->createStatementText();

        $this->assertEquals('DELETE FROM users;', $queryString);
    }
    
    public function testSimpleUpdate()
    {
        $query = new Query(new Connection(new DatabaseConfig()));

        $data = [
            'is_active' => false,
            'is_friendly' => true
        ];
        
        $queryString = $query->update('users')
            ->set($data)
            ->createStatementText();

        $this->assertEquals('UPDATE users SET `is_active` = ?, `is_friendly` = ?;', $queryString);
    }

    /**
     * @expectedException Instasell\Instarecord\Database\QueryBuilderException
     * @expectedExceptionMessage Query format error
     */
    public function testCannotUpdateWithColumnIndexNumbers()
    {
        $query = new Query(new Connection(new DatabaseConfig()));

        $data = [true, false];

        $queryString = $query->update('users')
            ->set($data);
    }
    
    public function testSimpleInsertWithColumnNames()
    {
        $query = new Query(new Connection(new DatabaseConfig()));
        
        $data = [
            'id' => '1',
            'user_name' => 'Hank'
        ];

        $queryString = $query->insert()
            ->values($data)
            ->into('users')
            ->createStatementText();

        $this->assertEquals('INSERT INTO users (`id`, `user_name`) VALUES (?, ?);', $queryString);   
    }

    public function testSimpleInsertWithColumnIndexNumbers()
    {
        $query = new Query(new Connection(new DatabaseConfig()));

        $data = ['1', 'Hank'];

        $queryString = $query->insert()
            ->values($data)
            ->into('users')
            ->createStatementText();

        $this->assertEquals('INSERT INTO users VALUES (?, ?);', $queryString);
    }
    
    public function testLimitAndOffset()
    {
        $query = new Query(new Connection(new DatabaseConfig()));
        
        $queryString = $query->update('users')
            ->set(['a' => 'b'])
            ->from('users')
            ->limit(123)
            ->offset(456)
            ->createStatementText();

        $this->assertEquals('UPDATE users SET `a` = ? LIMIT 123 OFFSET 456;', $queryString);
    }

    public function testWhere()
    {
        $query = new Query(new Connection(new DatabaseConfig()));

        $queryString = $query->delete()
            ->from('fruits')
            ->where('type = ? AND `color` = ?', 'apples', 'red')
            ->limit(5)
            ->createStatementText();

        $this->assertEquals('DELETE FROM fruits WHERE type = ? AND `color` = ? LIMIT 5;', $queryString);
    }
    
    public function testStatementGeneration()
    {
        $config = new DatabaseConfig();
        $config->adapter = DatabaseAdapter::MYSQL;
        $config->username = TEST_USER_NAME;
        $config->password = TEST_PASSWORD;
        $config->database = TEST_DATABASE_NAME;

        $connection = new Connection($config);
        
        $query = new Query($connection);

        $this->assertFalse($connection->isOpen(), 'Creating a new Query should not result in the connection opening');
        
        $query = $query->delete()
            ->from('clothes')
            ->where('`type` = ? AND `color` = ?', 'tshirt', 'blue');
        
        $pdoStatementText = $query->createStatementText();
        $pdoStatement = $query->createStatement();
        
        $this->assertTrue($connection->isOpen(), 'Creating a PDO statement via a Query should result in the connection opening (lazy / on demand connecting)');
        $this->assertInstanceOf('\PDOStatement', $pdoStatement);
        $this->assertEquals($pdoStatementText, $pdoStatement->queryString, 'Generated PDO statement should match query text');
        
        ob_start();
        $pdoStatement->debugDumpParams();
        $debugParamsDump = ob_get_contents();
        ob_end_clean();
        
        $this->assertContains("Params:  2", $debugParamsDump,"Expecting two bound parameters");
    }
}