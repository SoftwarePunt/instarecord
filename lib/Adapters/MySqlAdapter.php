<?php

namespace SoftwarePunt\Instarecord\Adapters;

use SoftwarePunt\Instarecord\Config\DatabaseConfig;
use SoftwarePunt\Instarecord\DatabaseAdapter;

/**
 * Database adapter for MySQL servers.
 */
class MySqlAdapter extends DatabaseAdapter
{
    public function getDsnPrefix(): string
    {
        return "mysql";
    }
}
