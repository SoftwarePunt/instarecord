<?php

namespace Instasell\Instarecord\Database;

use Instasell\Instarecord\Config\ConfigException;
use Instasell\Instarecord\Config\DatabaseConfig;
use Instasell\Instarecord\DatabaseAdapter;

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
     * Constructs a new, uninitialized database connection for a given adapter.
     * 
     * @throws ConfigException
     * @param DatabaseConfig $config
     */
    public function __construct(DatabaseConfig $config)
    {
        $this->config = $config;
        $this->adapter = DatabaseAdapter::createInstance($config->adapter);
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
            $this->pdo = new \PDO($this->adapter->createDsn($this->config),
                $this->config->username, $this->config->password);
        } catch (\Exception $ex) {
            throw new DatabaseException("Database connection failed: {$ex->getMessage()}", $ex->getCode(), $ex);
        }
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
     * @throws DatabaseException
     * @param string $statementText The statement (query) text.
     * @param array $parameters The parameters to be bound.
     * @return \PDOStatement
     */
    public function executeStatement(string $statementText, array $parameters = []): \PDOStatement
    {
        // Ensure the connection is open
        $this->open();
        
        // Create the new PDO statement object based on provided text
        try {
            $statement = $this->pdo->prepare($statementText);
        }
        catch (\PDOException $ex) {
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
        if (!$statement->execute()) {
            $errorInfo = $statement->errorInfo();
            throw new DatabaseException("Query execution failure: {$errorInfo[2]}", $errorInfo[1]);
        }
        
        // If we got this far, connect & execute was a success and we have a result object
        return $statement;
    }

    /**
     * Gets the last primary key value that was inserted on this connection.
     * 
     * @return string|null
     */
    public function lastInsertId(): ?string
    {
        if ($this->isOpen()) {
            return $this->pdo->lastInsertId();
        }
        
        return null;
    }
}