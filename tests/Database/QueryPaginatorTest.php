<?php

namespace Instasell\Instarecord\Tests\Database;

use Instasell\Instarecord\Config\DatabaseConfig;
use Instasell\Instarecord\Database\Connection;
use Instasell\Instarecord\Database\Query;
use Instasell\Instarecord\Instarecord;
use Instasell\Instarecord\Tests\Samples\User;
use Instasell\Instarecord\Tests\Testing\TestDatabaseConfig;
use PHPUnit\Framework\TestCase;

class QueryPaginatorTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        $config = new TestDatabaseConfig();
        $connection = new Connection($config);

        Instarecord::config($config);

        $userQuery = User::query()
            ->delete();
    }

    public static function tearDownAfterClass()
    {
        $userQuery = User::query()
            ->delete();
    }

    public function testPaginateConstructor()
    {
        $config = new TestDatabaseConfig();
        $connection = new Connection($config);

        $query = new Query($connection);
        $paginator = $query->paginate();

        $this->assertInstanceOf("Instasell\Instarecord\Database\QueryPaginator", $paginator, "paginate() call should construct a new QueryPaginator");
        $this->assertSame($paginator::DEFAULT_PAGE_SIZE, $paginator->getQueryPageSize(), "Default page size should be set in constructor");
    }

    public function testPaginateCalculation()
    {
        // Create dummy user that will be excluded from our query
        $user = new User();
        $user->userName = "dummy-for-paginator";
        $user->save();

        // Create 10 users with "paginate-" prefix
        $firstUser = null;

        for ($i = 0; $i < 10; $i++) {
            $user = new User();
            $user->userName = "paginate-{$i}";
            $user->save();

            if ($i === 0) {
                $firstUser = $user;
            }
        }

        // Sanity check
        $this->assertEquals(11, User::query()->count()->querySingleValue());

        // Create paginator
        $paginator = User::query()
            ->where('user_name LIKE "paginate-%"')
            ->orderBy('user_name DESC')
            ->paginate();

        $paginator->setQueryPageSize(3); // 3 items per page = 4 total pages, of which 3 full pages

        // Test global calculations (for the whole query)
        $this->assertSame(3, $paginator->getQueryPageSize());
        $this->assertSame(3, $paginator->getLimit());
        $this->assertSame(4, $paginator->getPageCount());
        $this->assertSame(10, $paginator->getTotalItemCount());

        // Assert that default page is index zero (first page)
        $this->assertSame(0, $paginator->getPageIndex());
        $this->assertTrue($paginator->getIsFirstPage());
        $this->assertTrue($paginator->getIsValidPage());
        $this->assertEquals(3, $paginator->getItemCountOnPage());

        // Try paginating to the last page, and verify it is calculated correctly
        $paginator->setPageIndex(3);

        $this->assertTrue($paginator->getIsValidPage());
        $this->assertFalse($paginator->getIsFirstPage());
        $this->assertTrue($paginator->getIsLastPage());
        $this->assertSame(1, $paginator->getItemCountOnPage());
        $this->assertEquals(9, $paginator->getOffset());

        // Run the derived query, and verify we only get one result (in our DESC sort, this should be the FIRST user)
        $query = $paginator->getPaginatedQuery();
        $results = $query->queryAllModels();

        $this->assertEquals([$firstUser], $results);
    }

    public function testPaginateCalculationWithZeroResults()
    {
        $paginator = User::query()
            ->where('id < 0')
            ->paginate();

        $this->assertSame(0, $paginator->getTotalItemCount());
        $this->assertSame(0, $paginator->getPageCount());
        $this->assertTrue($paginator->getIsFirstPage());
        $this->assertTrue($paginator->getIsLastPage());
        $this->assertTrue($paginator->getIsValidPage());
    }
}
