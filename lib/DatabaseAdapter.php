<?php

namespace Instasell\Instarecord;

use Instasell\Instarecord\Config\ConfigException;
use Instasell\Instarecord\Config\DatabaseConfig;

/**
 * Represents a database adapter, which provides database engine specific information and functionality.
 */
abstract class DatabaseAdapter
{
    const MYSQL = 'Instasell\Instarecord\Adapters\MySqlAdapter';

    /**
     * Generates and returns the service DSN for this database server based on the provided config.
     * 
     * @param DatabaseConfig $config The configuration object to create a DSN for.
     * @return string
     */
    public abstract function createDsn(DatabaseConfig $config): string;

    /**
     * Parses a service DSN into a DatabaseConfig instance.
     *
     * @param string $dsn The DSN string to parse.
     * @return DatabaseConfig
     */
    public abstract function parseDsn(string $dsn): DatabaseConfig;

    /**
     * Creates and returns an DatabaseAdapter instance based on its class name.
     * 
     * @param string $fqClassName The fully qualified class name.
     * @throws ConfigException
     * @return DatabaseAdapter
     */
    public static function createInstance(string $fqClassName): DatabaseAdapter
    {
        if (!class_exists($fqClassName)) {
            throw new ConfigException("Adapter class does not exist: {$fqClassName}");
        }
        
        $adapter = new $fqClassName();
        
        if (!$adapter instanceof DatabaseAdapter) {
            throw new ConfigException("Class is not a valid DatabaseAdapter: {$fqClassName}");
        }
        
        return $adapter;
    }
}
