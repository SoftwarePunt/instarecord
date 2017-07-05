<?php

namespace Instasell\Instarecord\Tests\Database;

use Instasell\Instarecord\Config\DatabaseConfig;
use Instasell\Instarecord\Database\Connection;
use Instasell\Instarecord\Database\Query;
use Instasell\Instarecord\Instarecord;
use Instasell\Instarecord\Tests\Samples\User;
use Instasell\Instarecord\Tests\Testing\TestDatabaseConfig;
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
    
    public function testSimpleSelectCount()
    {
        $query = new Query(new Connection(new DatabaseConfig()));

        $queryString = $query->count('num')
            ->from('users')
            ->createStatementText();

        $this->assertEquals('SELECT COUNT(num) FROM users;', $queryString);
    }
    
    public function testPerformsSelectByDefault()
    {
        $query = new Query(new Connection(new DatabaseConfig()));

        $queryString = $query->from('users')
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

    public function testOrderBy()
    {
        $query = new Query(new Connection(new DatabaseConfig()));

        $queryString = $query->select('*')
            ->from('users')
            ->orderBy("some_order_value ASC")
            ->createStatementText();

        $this->assertEquals('SELECT * FROM users ORDER BY some_order_value ASC;', $queryString);
    }

    public function testOrderByWithParams()
    {
        $query = new Query(new Connection(new DatabaseConfig()));

        $queryString = $query->select('*')
            ->from('articles')
            ->orderBy("MATCH (title) AGAINST (?) DESC", 'searchQuery')
            ->createStatementText();

        $this->assertEquals('SELECT * FROM articles ORDER BY MATCH (title) AGAINST (?) DESC;', $queryString);
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

        $this->assertEquals('DELETE FROM fruits WHERE (type = ? AND `color` = ?) LIMIT 5;', $queryString);
    }

    public function testWhereWithAnd()
    {
        $query = new Query(new Connection(new DatabaseConfig()));

        $queryString = $query->delete()
            ->from('fruits')
            ->where('type = ?', 'fruit')
            ->andWhere('color IN (?)', ['red', 'blue'])
            ->andWhere('tastes_nice = 1')
            ->createStatementText();

        $this->assertEquals('DELETE FROM fruits WHERE (type = ?) AND (color IN (?, ?)) AND (tastes_nice = 1);', $queryString);
    }
    
    public function testInnerJoin()
    {
        $query = new Query(new Connection(new DatabaseConfig()));

        $queryString = $query->select()
            ->from('orders')
            ->innerJoin('payments ON (payments.order_id = orders.id)')
            ->createStatementText();

        $this->assertEquals('SELECT * FROM orders INNER JOIN payments ON (payments.order_id = orders.id);', $queryString);
    }

    public function testLeftJoin()
    {
        $query = new Query(new Connection(new DatabaseConfig()));

        $queryString = $query->select()
            ->from('orders')
            ->leftJoin('payments ON (payments.order_id = orders.id)')
            ->createStatementText();

        $this->assertEquals('SELECT * FROM orders LEFT JOIN payments ON (payments.order_id = orders.id);', $queryString);
    }

    public function testRightJoin()
    {
        $query = new Query(new Connection(new DatabaseConfig()));

        $queryString = $query->select()
            ->from('orders')
            ->rightJoin('payments ON (payments.order_id = orders.id)')
            ->createStatementText();

        $this->assertEquals('SELECT * FROM orders RIGHT JOIN payments ON (payments.order_id = orders.id);', $queryString);
    }

    /**
     * @runInSeparateProcess 
     */
    public function testWhereWithBoundArray()
    {
        $config = new TestDatabaseConfig();

        Instarecord::config($config);
        
        $query = new Query(Instarecord::connection());
        
        $testUserA = new User();
        $testUserA->userName = 'ArrayGuyOne';
        $testUserA->save();

        $testUserB = new User();
        $testUserB->userName = 'ArrayGuyTwo';
        $testUserB->save();
        
        $queryString = $query->select()
            ->from('users')
            ->where('id > ? AND id IN (?) AND id >= ?', 0, [$testUserA->id, $testUserB->id, 'banana'], 0)
            ->createStatementText();

        // Test query formatting
        $this->assertEquals('SELECT * FROM users WHERE (id > ? AND id IN (?, ?, ?) AND id >= ?);', $queryString);
        
        // Test actual execution, expecting two rows
        $rows = $query->queryAllRows();
        $this->assertCount(2, $rows);
    }

    /**
     * @runInSeparateProcess
     */
    public function testWhereWithDateTimeValueTransform()
    {
        $dateTime = new \DateTime();
        $dateTime->setTimestamp(123456789);
        
        $query = Instarecord::query()->select()
            ->from('users')
            ->where('created_at = ?', $dateTime);
        $queryString = $query->createStatementText();
        
        $this->assertEquals('SELECT * FROM users WHERE (created_at = ?);', $queryString);
        $this->assertEquals(['1973-11-29 21:33:09'], $query->getBoundParametersForGeneratedStatement());
    }

    /**
     * @expectedException Instasell\Instarecord\Database\DatabaseException
     * @expectedExceptionMessage Table 'testdb.fruits' doesn't exist
     */
    public function testExecute()
    {
        $config = new TestDatabaseConfig();
        $connection = new Connection($config);

        $query = new Query($connection);

        $query->delete()
            ->from('fruits')
            ->where('type = ? AND `color` = ?', 'apples', 'red')
            ->limit(5)
            ->execute();
        
        // Testing exception because it will show several things:
        //  a) Communication / connection is up and running, execute is working
        //  b) Syntax of the command was valid and correct because of the table-specific error
        //  c) Error handling for execute failures is working correctly
    }

    public function testExecuteInsertWithAutoIncrement()
    {
        $config = new TestDatabaseConfig();
        $connection = new Connection($config);

        $query = new Query($connection);

        $aiId = $query->insert()
            ->from('users')
            ->values(['user_name' => 'ai-me-yay'])
            ->executeInsert();
        
        $this->assertNotEmpty($aiId, 'Auto incremented ID return value expected');
        $this->assertGreaterThan(0, $aiId, 'Auto incremented ID return value non-negative ID expected');
    }

    /**
     * @depends testExecuteInsertWithAutoIncrement
     */
    public function testQueryAllRows()
    {
        $config = new TestDatabaseConfig();
        $connection = new Connection($config);

        $query = new Query($connection);
        $query->select('*');
        $query->from('users');
        $allRows = $query->queryAllRows();
        
        $this->assertNotEmpty($allRows, 'Expected a nonempty row resultset');
        $this->assertNotEmpty($allRows[0], 'Expected a row subarray in the resultset');
        $this->assertArrayHasKey('user_name', $allRows[0], 'Expected a row subarray as assoc array with column names as indexes');
    }

    /**
     * @depends testExecuteInsertWithAutoIncrement
     */
    public function testQuerySingleRow()
    {
        $config = new TestDatabaseConfig();
        $connection = new Connection($config);

        $query = new Query($connection);
        $query->select('*');
        $query->from('users');
        $singleRow = $query->querySingleRow();

        $this->assertNotEmpty($singleRow, 'Expected a single row as result');
        $this->assertArrayHasKey('user_name', $singleRow, 'Expected a row as assoc array with column names as indexes');
    }

    /**
     * @depends testExecuteInsertWithAutoIncrement
     */
    public function testQuerySingleRowReturnsNullOnNoResult()
    {
        $config = new TestDatabaseConfig();
        $connection = new Connection($config);

        $query = new Query($connection);
        $query->select('*');
        $query->from('users');
        $query->where('id < 0');
        $singleRow = $query->querySingleRow();

        $this->assertNull($singleRow, 'Expected a single NULL as result');
    }

    /**
     * @depends testExecuteInsertWithAutoIncrement
     */
    public function testQuerySingleRowReturnsNullIfNotFound()
    {
        $config = new TestDatabaseConfig();
        $connection = new Connection($config);

        $query = new Query($connection);
        $query->select('*');
        $query->from('users');
        $query->where('id < 0');
        $singleRow = $query->querySingleRow();

        $this->assertNull($singleRow, 'Expected no results, with a return value of NULL');
    }

    /**
     * @runInSeparateProcess 
     */
    public function testQuerySingleValue()
    {
        $config = new TestDatabaseConfig();
        $connection = new Connection($config);
        
        Instarecord::config($config);

        $testUser = new User();
        $testUser->userName = 'HenkTheSingleGuy';
        $testUser->save();
        
        $query = new Query($connection);
        $query->select('user_name');
        $query->from('users');
        $query->where('id = ?', $testUser->id);
        $firstValue = $query->querySingleValue();
        
        $this->assertEquals($testUser->userName, $firstValue);
    }

    /**
     * @runInSeparateProcess
     */
    public function testQuerySingleValueArray()
    {
        $config = new TestDatabaseConfig();
        $connection = new Connection($config);

        Instarecord::config($config);

        $testUserA = new User();
        $testUserA->userName = 'ArrItemOneSVA';
        $testUserA->save();

        $testUserB = new User();
        $testUserB->userName = 'ArrItemTwoSVA';
        $testUserB->save();

        $query = new Query($connection);
        $query->select('user_name');
        $query->from('users');
        
        $sva = $query->querySingleValueArray();

        $this->assertNotEmpty($sva);
        $this->assertContains('ArrItemOneSVA', $sva);
        $this->assertContains('ArrItemTwoSVA', $sva);
    }

    /**
     * @runInSeparateProcess
     */
    public function testQueryKeyValueArray()
    {
        $config = new TestDatabaseConfig();
        $connection = new Connection($config);

        Instarecord::config($config);

        $testUserA = new User();
        $testUserA->userName = 'ArrItemOneKVA';
        $testUserA->save();

        $testUserB = new User();
        $testUserB->userName = 'ArrItemTwoKVA';
        $testUserB->save();

        $query = new Query($connection);
        $query->select('id as `KEY`, user_name as `VALUE`');
        $query->from('users');

        $kva = $query->queryKeyValueArray();

        $this->assertNotEmpty($kva);
        $this->assertContains('ArrItemOneKVA', $kva);
        $this->assertContains('ArrItemTwoKVA', $kva);
        $this->assertEquals($testUserA->userName, $kva[$testUserA->id]);
        $this->assertEquals($testUserB->userName, $kva[$testUserB->id]);
    }
}