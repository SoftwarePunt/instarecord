<?php

namespace SoftwarePunt\Instarecord\Tests\Database;

use PHPUnit\Framework\TestCase;
use SoftwarePunt\Instarecord\Config\DatabaseConfig;
use SoftwarePunt\Instarecord\Database\Connection;
use SoftwarePunt\Instarecord\Database\Query;
use SoftwarePunt\Instarecord\Instarecord;
use SoftwarePunt\Instarecord\Tests\Samples\User;
use SoftwarePunt\Instarecord\Tests\Testing\TestDatabaseConfig;

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

    public function testSimpleSelectCountWithDefaultValue()
    {
        $query = new Query(new Connection(new DatabaseConfig()));

        $queryString = $query->count()
            ->from('users')
            ->createStatementText();

        $this->assertEquals('SELECT COUNT(*) FROM users;', $queryString);
    }

    public function testPerformsSelectByDefault()
    {
        $query = new Query(new Connection(new DatabaseConfig()));

        $queryString = $query->from('users')
            ->createStatementText();

        $this->assertEquals('SELECT * FROM users;', $queryString);
    }

    public function testBoundSelect()
    {
        $query = (new Query(new Connection(new DatabaseConfig())))
            ->from('users')
            ->select('user_name, MATCH (user_name) AGAINST (? IN BOOLEAN MODE) AS score', "*some bound text*");

        $queryString = $query->createStatementText();
        $queryParams = $query->getBoundParametersForGeneratedStatement();

        $this->assertEquals('SELECT user_name, MATCH (user_name) AGAINST (? IN BOOLEAN MODE) AS score FROM users;', $queryString);
        $this->assertEquals("*some bound text*", $queryParams[0]);
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

    public function testSimpleUpdateWithDateTimeParam()
    {
        $query = new Query(new Connection(new DatabaseConfig()));

        $query = $query->update('users')
            ->set(['last_active' => new \DateTime('2017-06-05 01:02:03')]);

        $queryString = $query->createStatementText();
        $queryParams = $query->getBoundParametersForGeneratedStatement();

        $this->assertEquals('UPDATE users SET `last_active` = ?;', $queryString);
        $this->assertEquals('2017-06-05 01:02:03', $queryParams[0]);
    }

    public function testRawUpdateWithoutParams()
    {
        $query = new Query(new Connection(new DatabaseConfig()));

        $queryString = $query->update('users')
            ->set("is_active = 1")
            ->createStatementText();

        $this->assertEquals('UPDATE users SET is_active = 1;', $queryString);
    }

    public function testRawUpdateWithParams()
    {
        $query = new Query(new Connection(new DatabaseConfig()));

        $query = $query->update('users')
            ->set("is_active = 1, name = ?", "blah");

        $queryString = $query->createStatementText();
        $queryParams = $query->getBoundParametersForGeneratedStatement();

        $this->assertEquals('UPDATE users SET is_active = 1, name = ?;', $queryString);
        $this->assertEquals("blah", $queryParams[0]);
    }

    public function testCannotUpdateWithColumnIndexNumbers()
    {
        $this->expectException("SoftwarePunt\Instarecord\Database\QueryBuilderException");
        $this->expectExceptionMessage("Query format error");

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

    /**
     * @depends testSimpleInsertWithColumnNames
     */
    public function testInsertIgnore()
    {
        $query = new Query(new Connection(new DatabaseConfig()));

        $data = [
            'id' => '1',
            'user_name' => 'Hank'
        ];

        $queryString = $query->insertIgnore()
            ->values($data)
            ->into('users')
            ->createStatementText();

        $this->assertEquals('INSERT IGNORE INTO users (`id`, `user_name`) VALUES (?, ?);', $queryString);
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

    public function testInsertWithOnDuplicateKeyUpdate()
    {
        $query = new Query(new Connection(new DatabaseConfig()));

        $updateData = ['name' => 'Henk'];
        $insertData = ['id' => '1'] + $updateData;

        $queryString = $query->insert()
            ->into('users')
            ->values($insertData)
            ->onDuplicateKeyUpdate($updateData)
            ->createStatementText();

        $this->assertEquals('INSERT INTO users (`id`, `name`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `name` = ?;', $queryString);
    }

    public function testInsertWithOnDuplicateKeyUpdateWithLastInsertId()
    {
        $query = new Query(new Connection(new DatabaseConfig()));

        $updateData = ['id' => 123, 'name' => 'Henk'];

        $queryString = $query->insert()
            ->into('users')
            ->values($updateData)
            ->onDuplicateKeyUpdate($updateData, 'id')
            ->createStatementText();

        $this->assertEquals('INSERT INTO users (`id`, `name`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `name` = ?;', $queryString);
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

    public function testGroupBy()
    {
        $query = new Query(new Connection(new DatabaseConfig()));

        $queryString = $query->select('*')
            ->from('users')
            ->groupBy("id")
            ->createStatementText();

        $this->assertEquals('SELECT * FROM users GROUP BY id;', $queryString);
    }

    public function testGroupByWithParams()
    {
        $query = new Query(new Connection(new DatabaseConfig()));

        $queryString = $query->select('*')
            ->from('articles')
            ->groupBy('id HAVING COUNT(id) > ?', 1)
            ->createStatementText();

        $this->assertEquals('SELECT * FROM articles GROUP BY id HAVING COUNT(id) > ?;', $queryString);
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

    public function testWhereWithMultipleINs()
    {
        $query = new Query(new Connection(new DatabaseConfig()));

        $query = $query->delete()
            ->from('fruits')
            ->where('color = ? OR (color IN (?)) OR (color IN (?))',
                'pink', ['red', 'blue', 'orange'], ['blurple', 'black']);

        $queryString = $query->createStatementText();

        $this->assertEquals('DELETE FROM fruits WHERE (color = ? OR (color IN (?, ?, ?)) OR (color IN (?, ?)));', $queryString);

        $this->assertEquals([
            'pink',
            'red',
            'blue',
            'orange',
            'blurple',
            'black'
        ], $query->getBoundParametersForGeneratedStatement());
    }

    public function testHaving()
    {
        $query = new Query(new Connection(new DatabaseConfig()));

        $queryString = $query->delete()
            ->from('fruits')
            ->having('type = ? AND `color` = ?', 'apples', 'red')
            ->limit(5)
            ->createStatementText();

        $this->assertEquals('DELETE FROM fruits HAVING (type = ? AND `color` = ?) LIMIT 5;', $queryString);
    }

    public function testHavingWithAnd()
    {
        $query = new Query(new Connection(new DatabaseConfig()));

        $queryString = $query->delete()
            ->from('fruits')
            ->having('type = ?', 'fruit')
            ->andHaving('color IN (?)', ['red', 'blue'])
            ->andHaving('tastes_nice = 1')
            ->createStatementText();

        $this->assertEquals('DELETE FROM fruits HAVING (type = ?) AND (color IN (?, ?)) AND (tastes_nice = 1);', $queryString);
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

    public function testExecute()
    {
        $this->expectException("SoftwarePunt\Instarecord\Database\DatabaseException");
        $this->expectExceptionMessage(".fruits' doesn't exist");

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
        $this->assertIsInt($aiId, 'Auto incremented ID should be an integer');
    }

    /**
     * @depends testExecuteInsertWithAutoIncrement
     */
    public function testExecuteReturnsRowCount()
    {
        $config = new TestDatabaseConfig();
        $connection = new Connection($config);

        (new Query($connection))->insert()
            ->from('users')
            ->values(['user_name' => 'delete-this-row-1'])
            ->executeInsert();

        (new Query($connection))->insert()
            ->from('users')
            ->values(['user_name' => 'delete-this-row-2'])
            ->executeInsert();

        $rowCount = (new Query($connection))->delete()
            ->from('users')
            ->where('user_name LIKE ?', 'delete-this-row-%')
            ->execute();

        $this->assertGreaterThanOrEqual(2, $rowCount);
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
