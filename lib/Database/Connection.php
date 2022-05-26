<?php

namespace SoftwarePunt\Instarecord\Database;

use SoftwarePunt\Instarecord\Config\ConfigException;
use SoftwarePunt\Instarecord\Config\DatabaseConfig;
use SoftwarePunt\Instarecord\DatabaseAdapter;
use SoftwarePunt\Instarecord\Logging\QueryLogger;

/**
 * Represents a connection to the database.
 */
class Connection
{
    /**
     * The configuration object used for this connection.
     *
     * @var DatabaseConfig
     */
    protected $config;

    /**
     * The adapter used to communicate with the database.
     *
     * @var DatabaseAdapter
     */
    protected $adapter;

    /**
     * The PDO connection.
     *
     * @var \PDO
     */
    protected $pdo;

    /**
     * The query logger, if applicable.
     *
     * @var QueryLogger
     */
    protected $queryLogger;

    /**
     * Constructs a new, uninitialized database connection for a given adapter.
     *
     * @param DatabaseConfig $config
     * @throws ConfigException
     */
    public function __construct(DatabaseConfig $config)
    {
        $this->config = $config;
        $this->adapter = DatabaseAdapter::createInstance($config->adapter);
        $this->queryLogger = null;
    }

    /**
     * Sets the query logger on this connection instance.
     *
     * @param QueryLogger $logger
     */
    public function setQueryLogger(QueryLogger $logger)
    {
        $this->queryLogger = $logger;
    }

    /**
     * Attempts to open a database connection.
     * If the connection is already open, this function will do nothing.
     *
     * @throws DatabaseException
     */
    public function open(): void
    {
        if ($this->isOpen()) {
            return;
        }

        try {
            $this->pdo = new \PDO($this->generateDsn(), $this->config->username, $this->config->password);
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT);
            $this->pdo->exec("SET NAMES {$this->config->charset};");
        } catch (\Exception $ex) {
            throw new DatabaseException("Database connection failed: {$ex->getMessage()}", $ex->getCode(), $ex);
        }
    }

    /**
     * Gets the raw PDO connection, opening the connection if necessary.
     *
     * @throws DatabaseException Throws if the database connection cannot be opened.
     * @return \PDO
     */
    public function getPdo(): \PDO
    {
        $this->open();
        return $this->pdo;
    }

    /**
     * @return string
     */
    public function generateDsn(): string
    {
        return $this->adapter->createDsn($this->config);
    }

    /**
     * Tries to close the connection if it is open.
     */
    public function close(): void
    {
        if (!$this->isOpen()) {
            return;
        }

        $this->pdo = null;
    }

    /**
     * Gets whether the database connection has been opened.
     *
     * @return bool
     */
    public function isOpen(): bool
    {
        return $this->pdo != null;
    }

    /**
     * Creates and executes a new PDO database statement, returning it if successful.
     * Causes the connection to open if it is currently closed.
     *
     * @param string $statementText The statement (query) text.
     * @param array $parameters The parameters to be bound.
     * @return \PDOStatement
     * @throws DatabaseException
     */
    public function executeStatement(string $statementText, array $parameters = []): \PDOStatement
    {
        // Ensure the connection is open
        $this->open();

        // Create the new PDO statement object based on provided text
        try {
            $statement = $this->pdo->prepare($statementText);
        } catch (\PDOException $ex) {
            throw new DatabaseException("Database error: Could not prepare a new statement: {$ex->getMessage()}", $ex->getCode(), $ex);
        }

        if (!$statement) {
            throw new DatabaseException("Database error: Could not prepare a new statement on this connection");
        }

        // Bind the parameters to the statement as values (one-based index numbers)
        $i = 0;

        foreach ($parameters as $paramNumber => $paramValue) {
            $statement->bindValue(++$i, $paramValue);
        }

        // Attempt to execute the statement, throwing an error on failure
        $queryTimeStart = microtime(true);
        $queryFailed = false;

        if (!$statement->execute()) {
            $queryFailed = true;
        }

        // Query complete, log it
        if ($this->queryLogger) {
            $queryTimeEnd = microtime(true);
            $queryRunTime = ($queryTimeEnd - $queryTimeStart);

            $this->queryLogger->onQueryComplete($statementText, $parameters, $queryRunTime);
        }

        // If this was a failure, throw up now
        if ($queryFailed) {
            $errorInfo = $statement->errorInfo();
            throw new DatabaseException("Query execution failure: {$errorInfo[2]}", $errorInfo[1]);
        }

        // If we got this far, connect & execute was a success and we have a result object
        return $statement;
    }

    /**
     * Gets the last primary key value that was inserted on this connection.
     *
     * @return int|null
     */
    public function lastInsertId(): ?int
    {
        if ($this->isOpen()) {
            return intval($this->pdo->lastInsertId());
        }

        return null;
    }

    /**
     * Begins a database transaction.
     * This turns off auto commit mode for statements.
     *
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commits a transaction to the database.
     * This re-enables auto commit mode for statements.
     *
     * @return bool
     */
    public function commitTransaction(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Rolls back a transaction, reverting to the previous state.
     * This re-enables auto commit mode for statements.
     *
     * @return bool
     */
    public function rollbackTransaction(): bool
    {
        return $this->pdo->rollBack();
    }
}