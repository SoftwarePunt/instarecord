<?php

namespace Instasell\Instarecord\Database;

/**
 * Represents a Instarecord database query for reading or writing data.
 */
class Query
{
    const QUERY_TYPE_SELECT = 0;
    const QUERY_TYPE_INSERT = 1;
    const QUERY_TYPE_UPDATE = 2;
    const QUERY_TYPE_DELETE = 3;

    /**
     * The connection on which this query is being performed.
     *
     * @var Connection
     */
    protected $connection;

    /**
     * The list of bound parameters to this query.
     *
     * @var array
     */
    protected $parameters;

    /**
     * The type of query to be executed.
     *
     * @see Query::QUERY_TYPE_*
     * @var int
     */
    protected $statementType;

    /**
     * The select statement for the query.
     *
     * @default *
     * @var string
     */
    protected $selectStatement;

    /**
     * The table name that is being queried on.
     *
     * @var string
     */
    protected $tableName;

    /**
     * The values to be updated or inserted (SET or VALUES).
     *
     * @var array
     */
    protected $dataValues;

    /**
     * Limit to apply to query results (max records to return or change).
     * If set to NULL, no limit should be applied.
     * 
     * @default null
     * @var int|null
     */
    protected $limit;

    /**
     * Offset to apply to query results (records to skip).
     * If set to NULL, no limit should be applied.
     * 
     * @default null
     * @var int|null
     */
    protected $offset;

    /**
     * Constructs a new, blank query.
     *
     * @param Connection $connection The connection to perform the query on.
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;

        $this->reset();
    }

    /**
     * Resets the query to a blank slate.
     *
     * @return Query
     */
    public function reset(): Query
    {
        $this->parameters = [];
        $this->statementType = self::QUERY_TYPE_SELECT;
        $this->selectStatement = "*";
        $this->tableName = null;
        $this->dataValues = [];
        $this->limit = null;
        $this->offset = null;

        return $this;
    }

    /**
     * Begins a SELECT statement.
     *
     * @param string $selectText The select text: which columns to select.
     * @return Query|$this
     */
    public function select(string $selectText): Query
    {
        $this->statementType = self::QUERY_TYPE_SELECT;
        $this->selectStatement = $selectText;
        return $this;
    }

    /**
     * Begins a INSERT statement.
     *
     * @return Query
     */
    public function insert(): Query
    {
        $this->statementType = self::QUERY_TYPE_INSERT;
        return $this;
    }

    /**
     * Begins a UPDATE statement.
     *
     * @param string $tableName
     * @return Query
     */
    public function update(string $tableName): Query
    {
        $this->statementType = self::QUERY_TYPE_UPDATE;
        $this->tableName = $tableName;
        return $this;
    }

    /**
     * Begins a DELETE statement.
     *
     * @return Query|$this
     */
    public function delete(): Query
    {
        $this->statementType = self::QUERY_TYPE_DELETE;
        return $this;
    }

    /**
     * Adds a FROM statement onto a SELECT or DELETE query.
     *
     * @param string $tableName
     * @return Query
     */
    public function from(string $tableName): Query
    {
        $this->tableName = $tableName;
        return $this;
    }

    /**
     * Adds an INTO statement onto a INSERT query.
     *
     * @param string $tableName
     * @return Query
     */
    public function into(string $tableName): Query
    {
        $this->tableName = $tableName;
        return $this;
    }

    /**
     * Sets the values to be insert on an INSERT statement.
     *
     * @param array $values Associative array of the values to be set, optionally indexed by column names.
     * @return Query
     */
    public function values(array $values): Query
    {
        $this->dataValues = $values;
        return $this;
    }

    /**
     * Sets the values to be SET on an UPDATE statement.
     *
     * @param array $values Associative array of the values to be set, indexed by column names.
     * @throws QueryBuilderException
     * @return Query|$this
     */
    public function set(array $values): Query
    {
        $keys = array_keys($values);
        $firstParameterKey = array_shift($keys);
        $valuesAreIndexedByName = is_string($firstParameterKey);

        if (!$valuesAreIndexedByName) {
            throw new QueryBuilderException("Query format error: The values in the SET block MUST be indexed by column name, not by column index number.");
        }

        $this->dataValues = $values;
        return $this;
    }

    /**
     * Applies an LIMIT to the statement.
     * 
     * @param int|null $limit Set limit to a number, or set to NULL or ZERO to make this query limitless.
     * @return Query|$this
     */
    public function limit(?int $limit): Query
    {
        $this->limit = ($limit && $limit > 0) ? $limit : null;
        return $this;
    }

    /**
     * Applies an OFFSET to the statement.
     *
     * @param int|null $offset Set offset amount to a number, or set to NULL or ZERO to not apply an offset.
     * @return Query|$this
     */
    public function offset(?int $offset): Query
    {
        $this->offset = ($offset && $offset > 0) ? $offset : null;
        return $this;
    }

    /**
     * Binds a query parameter.
     *
     * @param mixed $param
     * @return Query
     */
    private function bindParam($param): Query
    {
        $this->parameters[] = $param;
        return $this;
    }

    /**
     * Generates the statement (query) text.
     * This process will use the data configured on this query object to generate an SQL statement.
     *
     * Calling this function will result in all bound parameters being reset.
     *
     * @return string
     */
    public function createStatementText(): string
    {
        // Reset bound parameters
        $this->parameters = [];

        // Begin building query
        $statementText = '';

        // Statement header and table name
        if ($this->statementType === self::QUERY_TYPE_SELECT) {
            $statementText = "SELECT {$this->selectStatement} FROM {$this->tableName}";
        } else if ($this->statementType === self::QUERY_TYPE_INSERT) {
            $statementText = "INSERT INTO {$this->tableName}";
        } else if ($this->statementType === self::QUERY_TYPE_UPDATE) {
            $statementText = "UPDATE {$this->tableName}";
        } else if ($this->statementType === self::QUERY_TYPE_DELETE) {
            $statementText = "DELETE FROM {$this->tableName}";
        }

        // SET or VALUES data for INSERT and UPDATE statements
        if (!empty($this->dataValues)) {
            $columnIndexes = array_keys($this->dataValues);
            $columnValues = array_values($this->dataValues);

            if ($this->statementType == self::QUERY_TYPE_UPDATE) {
                $statementText .= " SET ";

                for ($i = 0; $i < count($columnValues); $i++) {
                    $columnName = $columnIndexes[$i];
                    $columnValue = $columnValues[$i];

                    if ($i > 0) {
                        $statementText .= ", ";
                    }

                    $statementText .= "`{$columnName}` = ?";
                    $this->bindParam($columnValue);
                }
            } else if ($this->statementType == self::QUERY_TYPE_INSERT) {
                $_columnIndexesForShift = $columnIndexes;
                $columnFirstIndex = array_shift($_columnIndexesForShift);
                $columnsAreIndexedByName = is_string($columnFirstIndex);

                if ($columnsAreIndexedByName) {
                    // VALUES for insert are indexed by column name, so prefix the insert list with column name hints
                    $statementText .= " (";

                    for ($i = 0; $i < count($columnIndexes); $i++) {
                        $columnName = $columnIndexes[$i];

                        if ($i > 0) {
                            $statementText .= ", ";
                        }

                        $statementText .= "`{$columnName}`";
                    }

                    $statementText .= ")";
                }

                $statementText .= " VALUES (";

                for ($i = 0; $i < count($columnValues); $i++) {
                    if ($i > 0) {
                        $statementText .= ", ";
                    }

                    $statementText .= "?";

                    $columnValue = $columnValues[$i];
                    $this->bindParam($columnValue);
                }

                $statementText .= ")";
            }
        }
        
        // Apply LIMIT
        if ($this->limit) {
            $statementText .= " LIMIT {$this->limit}";
        }

        // Apply OFFSET
        if ($this->offset) {
            $statementText .= " OFFSET {$this->offset}";
        }

        $statementText .= ';';
        return $statementText;
    }

    /**
     * Returns the prepared PDO statement.
     *
     * @return \PDOStatement
     */
    public function createStatement(): \PDOStatement
    {
        // Prepare statement
        $statement = $this->connection->createStatement($this->createStatementText());

        // Bind parameters to it
        $i = 0;

        foreach ($this->parameters as $paramNumber => $paramValue) {
            $statement->bindParam(++$i, $paramValue);
        }

        // Ready to execute
        return $statement;
    }
}