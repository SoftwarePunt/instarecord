<?php

namespace Softwarepunt\Instarecord\Adapters;

use Softwarepunt\Instarecord\Config\DatabaseConfig;
use Softwarepunt\Instarecord\DatabaseAdapter;

/**
 * Database adapter for MySQL servers.
 */
class MySqlAdapter extends DatabaseAdapter
{
    /**
     * @inheritdoc
     */
    public function createDsn(DatabaseConfig $config): string
    {
        $dsn = "mysql:";

        if ($config->unix_socket) {
            $dsn .= "unix_socket={$config->unix_socket};";
        } else {
            if ($config->host) {
                if ($config->host === "localhost") {
                    $dsn .= "host=127.0.0.1;";
                } else {
                    $dsn .= "host={$config->host};";
                }
            }

            if ($config->port) {
                $dsn .= "port={$config->port};";
            }
        }

        if ($config->database) {
            $dsn .= "dbname={$config->database};";
        }

        if ($config->charset) {
            $dsn .= "charset={$config->charset};";
        }

        return rtrim($dsn, ';');
    }

    /**
     * Parses a service DSN into a DatabaseConfig instance.
     *
     * @param string $dsn The DSN string to parse.
     * @return DatabaseConfig
     */
    public function parseDsn(string $dsn): DatabaseConfig
    {
        $dbConfig = new DatabaseConfig();

        // Extract protocol
        $parts = explode(':', $dsn, 2);

        $protocol = $parts[0] ?? "mysql";
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
            }
        }

        return $dbConfig;
    }
}
