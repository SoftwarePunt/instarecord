<?php

namespace SoftwarePunt\Instarecord\Adapters;

use SoftwarePunt\Instarecord\DatabaseAdapter;

/**
 * Database adapter for PostgreSQL servers.
 */
class PostgreSqlAdapter extends DatabaseAdapter
{
    public function getQueryBacktick(): string
    {
        return '"';
    }

    public function getDsnPrefix(): string
    {
        return "pgsql";
    }

    protected function writeDsnPart(string &$dsn, string $component, mixed $value): void
    {
        if ($component === "charset") {
            if (str_starts_with($value, "utf8")) {
                $value = "UTF8";
            }
            parent::writeDsnPart($dsn, 'options', "'--client_encoding={$value}'");
            return;
        }

        parent::writeDsnPart($dsn, $component, $value);
    }
}
