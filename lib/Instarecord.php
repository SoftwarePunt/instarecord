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
     * @var string
     */
    protected $id;

    /**
     * Static reference to the database connection configuration.
     *
     * @var DatabaseConfig
     */
    protected $config;

    /**
     * Static reference to the open database connection.
     *
     * @var Connection
     */
    protected $connection;

    /**
     * @var Instarecord|null
     */
    protected static $instance;

    /**
     * @var Instarecord[]
     */
    protected static $instances = [];

    public function __construct(?string $id = null, bool $makePrimary = false)
    {
        $this->id = $id;

        if (empty($this->id)) {
            $this->id = "default_" . rand(10000, 99999);
        }

        if (!self::$instance || $makePrimary) {
            self::$instance = $this;
        }

        self::$instances[$this->id] = $this;
    }

    /**
     * Gets the ID for this Instarecord instance.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Returns the primary Instarecord instance.
     *
     * If no instance is marked as primary, this will return the first created Instance.
     * If no instance was created yet and $autoCreate is set to FALSE, this will return NULL.
     *
     * @param bool $autoCreate If true,
     * @return Instarecord|null Primary instance, first created instance, or NULL.
     */
    public static function instance(bool $autoCreate = true): ?Instarecord
    {
        if (!self::$instance && $autoCreate) {
            self::$instance = new Instarecord("default", true);
        }

        return self::$instance;
    }

    /**
     * Gets and returns an Instarecord instance by its id.
     *
     * @param string $instanceId
     * @return Instarecord|null The instance, or NULL if it the instance wasn't found.
     */
    public static function getInstanceById(string $instanceId): ?Instarecord
    {
        return self::$instances[$instanceId] ?? null;
    }

    /**
     * Returns the database configuration.
     *
     * @param $replaceConfigWith DatabaseConfig If provided, replace database config with this object.
     * @return DatabaseConfig The active database config.
     */
    public function getOrSetConfig(?DatabaseConfig $replaceConfigWith = null): DatabaseConfig
    {
        if ($replaceConfigWith) {
            $this->config = $replaceConfigWith;
        }
        
        if (!$this->config) {
            $this->config = new DatabaseConfig();
        }

        return $this->config;
    }

    /**
     * Returns the database configuration (on the primary instance).
     *
     * @param $replaceConfigWith DatabaseConfig If provided, replace database config with this object.
     * @return DatabaseConfig The active database config.
     */
    public static function config(?DatabaseConfig $replaceConfigWith = null): DatabaseConfig
    {
        return self::instance()->getOrSetConfig($replaceConfigWith);
    }

    /**
     * Returns the Instarecord database connection, creating it if not yet initialized.
     * It is important to configure Instarecord before referencing its connection.
     *
     * @param bool $forceReconnect
     * @return Connection
     */
    public function getConnection(bool $forceReconnect = false): Connection
    {
        if ($forceReconnect && $this->connection) {
            $this->connection->close();
            $this->connection = null;
        }

        if (!$this->connection) {
            $this->connection = new Connection($this->getOrSetConfig());
        }

        return $this->connection;
    }

    /**
     * Returns the Instarecord database connection, creating it if not yet initialized (on the primary instance).
     * It is important to configure Instarecord before referencing its connection.
     *
     * @param bool $forceReconnect
     * @return Connection
     */
    public static function connection(bool $forceReconnect = false): Connection
    {
        return self::instance()->getConnection($forceReconnect);
    }

    /**
     * Creates and returns a new database query.
     *
     * @return Query
     */
    public function createQuery(): Query
    {
        return new Query($this->getConnection());
    }

    /**
     * Creates and returns a new database query (on the primary instance).
     *
     * @return Query|null
     */
    public static function query(): ?Query
    {
        return self::instance()->createQuery();
    }
}
