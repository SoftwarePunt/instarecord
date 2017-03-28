<?php

namespace Instasell\Instarecord\Tests\Database;

use Instasell\Instarecord\Config\DatabaseConfig;
use Instasell\Instarecord\Database\Connection;
use Instasell\Instarecord\Database\Query;
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
}