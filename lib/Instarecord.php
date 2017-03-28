<?php

namespace Instasell\Instarecord;

use Instasell\Instarecord\Config\DatabaseConfig;
use Instasell\Instarecord\Database\Connection;
use Instasell\Instarecord\Database\Query;

/**
 * Static core logic for Instarecord.
 */
class Instarecord
{
    /**
     * Static reference to the database connection configuration.
     *
     * @var DatabaseConfig
     */
    protected static $config;

    /**
     * Static reference to the open database connection.
     *
     * @var Connection
     */
    protected static $connection;

    /**
     * Returns the database configuration.
     *
     * @return DatabaseConfig
     */
    public static function config(): DatabaseConfig
    {
        if (!self::$config) {
            self::$config = new DatabaseConfig();
        }

        return self::$config;
    }

    /**
     * Returns the Instarecord database connection, creating it if not yet initialized, but does not open it.
     *
     * @param bool $forceReconnect
     * @return Connection
     */
    public static function connection(bool $forceReconnect = false): Connection
    {
        if ($forceReconnect && self::$connection) {
            self::$connection->close();
            self::$connection = null;
        }

        if (!self::$connection) {
            self::$connection = new Connection(self::config());
        }

        return self::$connection;
    }

    /**
     * Creates and returns a new database query.
     *
     * @return Query
     */
    public static function query(): Query
    {
        return new Query(self::connection());
    }
}