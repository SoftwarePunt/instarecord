<?php

namespace Instasell\Instarecord\Tests\Database;

use Instasell\Instarecord\Database\Connection;
use Instasell\Instarecord\Logging\QueryLogger;
use Instasell\Instarecord\Tests\Testing\TestDatabaseConfig;
use PHPUnit\Framework\TestCase;

class QueryLoggerTest extends TestCase
{
    /**
     * @var array
     */
    public static $queriesLogged = [];

    /**
     * @var QueryLogger
     */
    public static $queryLogger; 

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        
        // Attach query logger that writes logged stuff to an array for testing purposes
        self::$queryLogger = new class extends QueryLoggerTest implements QueryLogger
        {
            public function onQueryComplete(string $queryString, array $parameters, float $queryRunTime): void
            {
                self::$queriesLogged[] = [
                    'q' => $queryString,
                    'p' => $parameters,
                    'r' => $queryRunTime
                ];
            }
        };
    }
    
    public function setUp()
    {
        parent::setUp(); 
        
        // Reset queries logged before each test
        self::$queriesLogged = [];
    }

    public function testQueryLoggerAttachesAndGetsCallback()
    {
        $config = new TestDatabaseConfig();
        $connection = new Connection($config);
        
        $connection->setQueryLogger(self::$queryLogger);
        
        $statementResult = $connection->executeStatement("SELECT * FROM users;");
        
        $this->assertNotEmpty(self::$queriesLogged, 'Expected a query to be logged');
        $loggedQuery = self::$queriesLogged[0];
        
        $this->assertEquals("SELECT * FROM users;", $loggedQuery['q'], 'Expected executed query to match logged query');
        $this->assertEquals([], $loggedQuery['p'], 'Expected executed params to match logged query');
        $this->assertGreaterThan(0.0, $loggedQuery['r'], 'Expected execution time to be nonzero');
    }
}
