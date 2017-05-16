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
     * Controls the ORDER BY structure.
     * 
     * @default null
     * @var string|null
     */
    protected $orderBy;

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
     * Contains an array of WHERE statement.
     *
     * Each entry in this array is another row (sub array):
     * - Parameter one (index zero) is the raw query text
     * - Each parameter following it is a bound parameter
     *
     * @var array
     */
    protected $whereStatements;

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
     * @return Query|$this
     */
    public function reset(): Query
    {
        $this->parameters = [];
        $this->statementType = self::QUERY_TYPE_SELECT;
        $this->selectStatement = "*";
        $this->tableName = null;
        $this->dataValues = [];
        $this->orderBy = null;
        $this->limit = null;
        $this->offset = null;
        $this->whereStatements = [];

        return $this;
    }

    /**
     * Begins a SELECT statement.
     *
     * @param string $selectText The select text: which columns to select. Defaults to "*". Unsafe value.
     * @return Query|$this
     */
    public function select(string $selectText = '*'): Query
    {
        $this->statementType = self::QUERY_TYPE_SELECT;
        $this->selectStatement = $selectText;
        return $this;
    }

    /**
     * Begins a SELECT COUNT(x) statement.
     *
     * @param string $countColumn The COUNT() text: which column to use for count. Defaults to "id". Unsafe value.
     * @return Query|$this
     */
    public function count(string $countColumn = 'id'): Query
    {
        $this->statementType = self::QUERY_TYPE_SELECT;
        $this->selectStatement = "COUNT({$countColumn})";
        return $this;
    }

    /**
     * Begins a INSERT statement.
     *
     * @return Query|$this
     */
    public function insert(): Query
    {
        $this->statementType = self::QUERY_TYPE_INSERT;
        return $this;
    }

    /**
     * Begins a UPDATE statement.
     *
     * @param string|null $tableName
     * @return Query|$this
     */
    public function update(?string $tableName = null): Query
    {
        $this->statementType = self::QUERY_TYPE_UPDATE;

        if ($tableName) {
            $this->tableName = $tableName;
        }

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
     * @return Query|$this
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
     * @return Query|$this
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
     * @return Query|$this
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
     * Processes a given $statementText and a set of $parameters and its sub parameters.
     * 
     * @param string $statementText The raw statement text / SQL to bind.
     * @param array $params The list of parameters to be bound to the statement text.
     * @throws QueryBuilderException
     * @return array A statement row, where index 0 contains the statement text and other values represent the params.
     */
    protected function processStatementParameters(string $statementText, array $params): array
    {
        // Verify parameter count to prevent (to aid the developer, really)
        $paramCountExpected = substr_count($statementText, '?');
        $paramCountActual = count($params);

        if ($paramCountExpected !== $paramCountActual) {
            throw new QueryBuilderException("Query parameter error: Expected {$paramCountExpected} bound parameters, but got {$paramCountActual} for statement \"{$statementText}\".");
        }
        
        // Cool, now let's get to work...
        $finalizedRow = [$statementText];

        for ($paramIdx = 0; $paramIdx < count($params); $paramIdx++) {
            $param = $params[$paramIdx];

            if (is_array($param)) {
                $paramSubCount = count($param);

                if ($paramSubCount == 0) {
                    // Empty array, bind empty string, not sure what else to do!
                    $whereStatement[] = '';
                } else {
                    // We have an array param, expand the "?" marker to multiple question marks and bind each as a 
                    // new, separate parameter to the statement.

                    // Example: WHERE bla = ? AND id IN(?)
                    // The $paramIdx will be #1 - 2nd item - so find the corresponding 2nd ? marker and modify it.

                    $markerOffset = 0;
                    $markerSkip = $paramIdx;
                    $markerIdx = 0;

                    while (true) {
                        $markerIdx = strpos($statementText, '?', $markerOffset);

                        if ($markerSkip <= 0) {
                            break;
                        } else {
                            $markerOffset += $markerIdx + 1;
                        }

                        $markerSkip--;
                    }

                    // We should now have the marker position, add additional markers
                    $extraMarkers = $paramSubCount - 1;

                    if ($extraMarkers > 0) {
                        $extraMarkersStr = "";

                        for ($i = 0; $i < $extraMarkers; $i++) {
                            $extraMarkersStr .= ", ?";
                        }

                        $statementText = substr_replace($statementText, $extraMarkersStr, $markerIdx + 1, 0);
                        $finalizedRow[0] = $statementText;
                    }

                    // Bind each parameter
                    foreach ($param as $subParam) {
                        $finalizedRow[] = $subParam;
                    }
                }
            } else {
                $finalizedRow[] = $param;
            }
        }

        return $finalizedRow;
    }
    
    /**
     * Sets the WHERE clause to the query.
     * Clears any previous WHERE clauses when called. 
     * 
     * Use andWhere() to combine different WHERE blocks.
     *
     * @param string $statementText Raw SQL "WHERE" statement text.
     * @param array ...$params Bound parameter list.
     * @see andWhere()
     * @throws QueryBuilderException
     * @return Query|$this
     */
    public function where(string $statementText, ...$params): Query
    {
        // Register the WHERE statement data as the ONLY row
        $whereStatement = $this->processStatementParameters($statementText, $params);
        $this->whereStatements = [$whereStatement];
        return $this;
    }

    /**
     * Adds an additional WHERE clause to the query.
     * Groups multiple where blocks using "WHERE (x) AND (y) AND (z)" syntax.
     * 
     * Use where() to clear all where clauses and set a new one.
     *
     * @param string $statementText Raw SQL "WHERE" statement text.
     * @param array ...$params Bound parameter list.
     * @see where()
     * @throws QueryBuilderException
     * @return Query|$this
     */
    public function andWhere(string $statementText, ...$params): Query
    {
        // Register the WHERE statement data as a new row
        $whereStatement = $this->processStatementParameters($statementText, $params);
        $this->whereStatements[] = $whereStatement;
        return $this;
    }

    /**
     * Sets the ORDER BY statement on the query.
     * 
     * @param string $statementText Raw SQL for the "ORDER BY" statement text.
     * @param array ...$params Bound parameter list.
     * @throws QueryBuilderException
     * @return Query|$this
     */
    public function orderBy(string $statementText, ...$params): Query
    {
        // Process parameters and set ORDER BY data
        $statementRow = $this->processStatementParameters($statementText, $params);
        
        $this->orderBy = $statementRow[0];
        $this->orderByParams = array_splice($statementRow, 1);
        
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
     * @return Query|$this
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

        // Apply WHERE
        $firstWhere = true;
        
        if (!empty($this->whereStatements)) {
            foreach ($this->whereStatements as $statementData) {
                if (!$firstWhere) {
                    $statementText .= " AND (";
                } else {
                    $statementText .= " WHERE (";
                }
                
                $whereStatementText = array_shift($statementData);
                $statementText .= $whereStatementText;

                foreach ($statementData as $parameter) {
                    $this->bindParam($parameter);
                }

                $statementText .= ")";
                $firstWhere = false;
            }
        }
        
        // Apply ORDER BY
        if (!empty($this->orderBy)) {
            $statementText .= " ORDER BY {$this->orderBy}";
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
     * Generates the statement, executes it, checks it for errors, and returns it on success.
     *
     * @throws DatabaseException
     * @return \PDOStatement The executed statement.
     */
    protected function executeStatement(): \PDOStatement
    {
        return $this->connection->executeStatement($this->createStatementText(), $this->parameters);
    }

    /**
     * Executes the query statement without gathering results.
     *
     * @throws DatabaseException
     */
    public function execute(): void
    {
        $stmt = $this->executeStatement();
        $stmt->closeCursor();
        $stmt = null;
    }

    /**
     * Executes the query, retrieving the inserted auto incremented primary key (if any).
     *
     * @return null|string
     */
    public function executeInsert(): ?string
    {
        $this->execute();
        return $this->connection->lastInsertId();
    }

    /**
     * Executes the query, retrieves all data, and returns it in an associative array.
     *
     * @return array
     */
    public function queryAllRows(): array
    {
        $statement = $this->executeStatement();

        $results = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $statement->closeCursor();
        $statement = null;

        return $results;
    }

    /**
     * Executes the query, limiting it to one row, and retrieve and return only that row as an associative array.
     *
     * @return array|null
     */
    public function querySingleRow(): ?array
    {
        // Modify limit to one as we are executing this expecting no more than one row, let's not waste any effort...
        // Note: This is useless for primary key queries thanks to the optimizer, but still relevant for non-pk queries
        $originalLimit = $this->limit;
        $this->limit(1);

        // Execute statement, only read one row
        $statement = $this->executeStatement();
        $firstRow = $statement->fetch(\PDO::FETCH_ASSOC);

        // Close statement
        $statement->closeCursor();
        $statement = null;

        // Restore original limit
        $this->limit($originalLimit);

        // Return row, or null if row wasn't found
        if ($firstRow) {
            return $firstRow;
        }

        return null;
    }

    /**
     * Executes the query, returning only the first value from the first row when possible.
     *
     * @return null|string The retrieved value as a string, or NULL if retrieval was not possible.
     */
    public function querySingleValue(): ?string
    {
        $statement = $this->executeStatement();
        $firstCol = $statement->fetchColumn(0);

        if ($firstCol) {
            return $firstCol;
        }

        return null;
    }
}