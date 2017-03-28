<?php

namespace Instasell\Instarecord\Database;

/**
 * Represents a Instarecord database query for reading or writing data.
 */
class Query
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * Constructs a new, blank query.
     * 
     * @param Connection $connection The connection to perform the query on.
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection; 
    }
}