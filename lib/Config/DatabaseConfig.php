<?php

namespace SoftwarePunt\Instarecord\Config;

use SoftwarePunt\Instarecord\Database\Column;

/**
 * Database configuration object for Instarecord.
 * Represents the configuration used to initialize a single database connection.
 */
class DatabaseConfig
{
    /**
     * The fully qualified class name of the database adapter to be used.
     * 
     * @default SoftwarePunt\Instarecord\Adapters\MySqlAdapter
     * @var string
     */
    public $adapter = "SoftwarePunt\\Instarecord\\Adapters\\MySqlAdapter";

    /**
     * The unix socket to be used to connect to the database host, replacing host and port.
     * This is, as the name suggests, only supported on Unix-based systems.
     * 
     * @default null
     * @var string|null
     */
    public $unix_socket = null;
    
    /**
     * The hostname or IP address of the database host, optionally including port number.
     * 
     * @default localhost
     * @var string
     */
    public $host = "localhost";

    /**
     * Port number, to override the default one for the database.
     * If set to NULL, the default port number will be used instead.
     * 
     * @default null
     * @var int|null
     */
    public $port = null;

    /**
     * The name of the database to select.
     * 
     * @default db
     * @var string
     */
    public $database = "db";

    /**
     * The name of the database user to authenticate as.
     * 
     * @default root
     * @var string
     */
    public $username = "root";

    /**
     * The password of the database user to authenticate with.
     * 
     * @default null
     * @var string|null
     */
    public $password = null;

    /**
     * The character set to use when connecting to the database.
     * 
     * @default utf8mb4
     * @var string
     */
    public $charset = 'utf8mb4';

    /**
     * The default timezone identifier.
     * All date, datetime and times columns will be stored and retrieved with this timezone as basis.
     *
     * Changing this value will effectively change how each database value is interpreted, so beware.
     *
     * @default UTC
     * @var string
     */
    public $timezone = Column::DEFAULT_TIMEZONE;
}
