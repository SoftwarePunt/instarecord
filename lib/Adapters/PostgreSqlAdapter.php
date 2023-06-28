<?php

namespace SoftwarePunt\Instarecord\Adapters;

use SoftwarePunt\Instarecord\DatabaseAdapter;

/**
 * Database adapter for PostgreSQL servers.
 */
class PostgreSqlAdapter extends DatabaseAdapter
{
    public function getDsnPrefix(): string
    {
        return "pgsql";
    }

    protected function writeDsnPart(string &$dsn, string $component, mixed $value): void
    {
        if ($component === "charset") {
            parent::writeDsnPart($dsn, 'options', "'--client_encoding={$value}'");
            return;
        }

        parent::writeDsnPart($dsn, $component, $value);
    }
}
