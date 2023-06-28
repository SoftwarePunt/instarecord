<?php

namespace SoftwarePunt\Instarecord\Adapters;

use SoftwarePunt\Instarecord\Config\DatabaseConfig;
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
}
