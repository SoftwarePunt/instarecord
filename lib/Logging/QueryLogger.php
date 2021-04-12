<?php

namespace Softwarepunt\Instarecord\Logging;

/**
 * Interface for a class that is capable of receiving query logs.
 * A logger must be assigned to Instarecord before it will receive event notifications.
 */
interface QueryLogger
{
    /**
     * This function is called when an Instarecord query has been successfully executed on the database connection.
     *
     * @param string $queryString The prepared statement text that was executed.
     * @param array $parameters The parameters bound to the query.
     * @param float $queryRunTime The query run time, as measured on the server.
     */
    public function onQueryComplete(string $queryString, array $parameters, float $queryRunTime): void;
}