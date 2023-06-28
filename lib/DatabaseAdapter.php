<?php

namespace SoftwarePunt\Instarecord;

use SoftwarePunt\Instarecord\Adapters\MySqlAdapter;
use SoftwarePunt\Instarecord\Adapters\PostgreSqlAdapter;
use SoftwarePunt\Instarecord\Config\ConfigException;
use SoftwarePunt\Instarecord\Config\DatabaseConfig;

/**
 * Represents a database adapter, which provides database engine specific information and functionality.
 */
abstract class DatabaseAdapter
{
    const MySql = MySqlAdapter::class;
    const PostgreSql = PostgreSqlAdapter::class;

    /**
     * Gets the string delimiter used for system identifiers when building queries.
     */
    public function getQueryBacktick(): string
    {
        return '`';
    }

    /**
     * Gets the DSN prefix (without a colon).
     * e.g. "mysql" or "pgsql".
     */
    public abstract function getDsnPrefix(): string;

    /**
     * Generates and returns the service DSN for this database server based on the provided config.
     * 
     * @param DatabaseConfig $config The configuration object to create a DSN for.
     */
    public function createDsn(DatabaseConfig $config): string
    {
        $dsn = $this->getDsnPrefix() . ':';

        if ($config->unix_socket) {
            $this->writeDsnPart($dsn, "unix_socket", $config->unix_socket);
        } else {
            if ($config->host)
                $this->writeDsnPart($dsn, "host", $config->host);
            if ($config->port)
                $this->writeDsnPart($dsn, "port", $config->port);
        }

        if ($config->database)
            $this->writeDsnPart($dsn, "dbname", $config->database);
        if ($config->charset)
            $this->writeDsnPart($dsn, "charset", $config->charset);

        return rtrim($dsn, ';');
    }

    protected function writeDsnPart(string &$dsn, string $component, mixed $value): void
    {
        if ($component === "host" && $value === "localhost")
            $value = "127.0.0.1";

        $dsn .= "{$component}={$value};";
    }

    /**
     * Parses a service DSN into a DatabaseConfig instance.
     *
     * @param string $dsn The DSN string to parse.
     */
    public function parseDsn(string $dsn): DatabaseConfig
    {
        $dbConfig = new DatabaseConfig();
        $dbConfig->adapter = static::class;

        // Extract protocol
        $parts = explode(':', $dsn, 2);

        $protocol = $parts[0] ?? "";

        if ($protocol !== $this->getDsnPrefix())
            throw new \InvalidArgumentException("Invalid DSN for this adapter: expected {$this->getDsnPrefix()} prefix");

        $remainder = $parts[1] ?? "";

        // Extract components
        $components = explode(';', $remainder);

        foreach ($components as $component) {
            $componentParts = explode('=', $component, 2);

            $componentName = trim(strtolower($componentParts[0]));
            $componentValue = trim($componentParts[1]) ?? "";

            switch ($componentName) {
                case "unix_socket":
                    $dbConfig->unix_socket = $componentValue;
                    $dbConfig->host = null;
                    $dbConfig->port = null;
                    break;
                case "host":
                    $dbConfig->host = $componentValue;
                    break;
                case "port":
                    $dbConfig->port = intval($componentValue);
                    break;
                case "dbname":
                    $dbConfig->database = $componentValue;
                    break;
                case "charset";
                    $dbConfig->charset = $componentValue;
                    break;
                case "options":
                    $optionsRaw = trim($componentValue, " \t\n\r\0\x0B'\"");
                    $keyValue = explode('=', $optionsRaw, 2);
                    if (count($keyValue) === 2) {
                        $key = $keyValue[0];
                        $value = $keyValue[1];
                        if ($key === "--client_encoding") {
                            $dbConfig->charset = $value;
                        }
                    }
                    break;
            }
        }

        return $dbConfig;
    }

    /**
     * Creates and returns an DatabaseAdapter instance based on its class name.
     * 
     * @param string $fqClassName The fully qualified class name.
     * @throws ConfigException
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
