<?php

namespace Instasell\Instarecord\Adapters;

use Instasell\Instarecord\Config\DatabaseConfig;
use Instasell\Instarecord\DatabaseAdapter;

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
                $dsn .= "host={$config->host};";
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
}