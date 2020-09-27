<?php

namespace Instasell\Instarecord;

use Instasell\Instarecord\Config\ModelConfig;
use Instasell\Instarecord\Database\AutoApplicator;
use Instasell\Instarecord\Database\Column;
use Instasell\Instarecord\Database\ModelQuery;
use Instasell\Instarecord\Database\Table;

/**
 * The base class for all Instasell models.
 */
class Model
{
    /**
     * An array containing properties and their last known values.
     * This is used to track "dirty" (changed) properties.
     *
     * @var array
     */
    protected $_trackedModelValues;

    /**
     * Contains table and column information.
     *
     * @var Table
     */
    protected $_tableInfo;

    /**
     * Initializes a new instance of this model which can be inserted into the database.
     *
     * @param array|null $initialValues Optionally, an array of initial property values to set on the model.
     */
    public function __construct(?array $initialValues = [])
    {
        $this->_tableInfo = Table::getTableInfo(get_class($this));

        $this->setInitialValues($initialValues);
        $this->markAllPropertiesClean();
    }

    /**
     * Gets the actual table name.
     * Can be overriden by a model to provide a custom name.
     *
     * @return string
     */
    public function getTableName(): string
    {
        return Table::getDefaultTableName(get_class($this));
    }

    /**
     * Applies a set of initial values as properties on this model.
     *
     * @param array $initialValues A list of properties and their values, or columns and their values, or a mix thereof.
     */
    protected function setInitialValues(?array $initialValues): void
    {
        foreach ($this->getTableInfo()->getColumns() as $column) {
            $propertyName = $column->getPropertyName();
            $defaultValue = $this->getColumnForPropertyName($propertyName)->getDefaultValue();

            // [php-7.4]: Only assign default value if it's not null, or if null is explicitly allowed
            if (($defaultValue !== null) || ($column->getIsNullable() && $defaultValue === null)) {
                $this->$propertyName = $defaultValue;
            }
        }

        if ($initialValues) {
            $this->setColumnValues($initialValues);
        }
    }

    /**
     * Gets a list of the names of all this model's properties.
     *
     * Properties are public variables defined in the class that can be get or set.
     * Each property refers to a real database column and may reflect a relationship.
     *
     * @return array
     */
    public function getPropertyNames(): array
    {
        $rfClass = new \ReflectionClass($this);
        $rfProperties = $rfClass->getProperties(\ReflectionProperty::IS_PUBLIC);

        $properties = [];

        foreach ($rfProperties as $rfProperty) {
            if ($rfProperty->isStatic()) {
                continue;
            }

            $properties[] = $rfProperty->getName();
        }

        return $properties;
    }

    /**
     * Gets a key/value list of all properties and their values.
     *
     * @return array An array containing property values, indexed by property name.
     */
    public function getPropertyValues(): array
    {
        $propertyList = [];

        foreach ($this->getPropertyNames() as $propertyName) {
            // [php-7.4]: Work around "must not be accessed before initialization" errors with isset()
            $propVal = null;

            if (isset($this->$propertyName)) {
                $propVal = $this->$propertyName;
            }

            $propertyList[$propertyName] = $propVal;
        }

        return $propertyList;
    }

    /**
     * Gets a key/value list of all properties and their current values, but indexed by column name.
     * This is useful when you need column names but values in the correct PHP types.
     *
     * @see getColumnValues()
     * @see getPropertyValues()
     * @return array
     */
    public function getPropertyValuesWithColumnNames(): array
    {
        $properties = $this->getPropertyValues();
        $columns = [];

        foreach ($properties as $propertyName => $propertyValue) {
            $columnInfo = $this->getColumnForPropertyName($propertyName);

            if ($columnInfo) {
                $columnName = $columnInfo->getColumnName();

                $columns[$columnName] = $propertyValue;
            }
        }

        return $columns;
    }

    /**
     * Gets a key/value list of all columns and their values.
     * This involves the translation of
     *
     * @return array An array containing property values, indexed by property name.
     */
    public function getColumnValues(): array
    {
        $properties = $this->getPropertyValues();
        $columns = [];

        foreach ($properties as $propertyName => $propertyValue) {
            $columnInfo = $this->getColumnForPropertyName($propertyName);

            if ($columnInfo) {
                $columnName = $columnInfo->getColumnName();
                $columnValue = $columnInfo->formatDatabaseValue($propertyValue);

                $columns[$columnName] = $columnValue;
            }
        }

        return $columns;
    }

    /**
     * Applies a set of database values to this instance.
     *
     * @param array $values
     */
    public function setColumnValues(array $values): void
    {
        foreach ($values as $nameInArray => $valueInArray) {
            // Can we find the column by its name?
            $columnInfo = $this->getColumnByName($nameInArray);

            if (!$columnInfo) {
                // Can we find the column by its property name?
                $columnInfo = $this->getColumnForPropertyName($nameInArray);
            }

            if (!$columnInfo) {
                // Okay, we can't find this column at all, ignore this property
                continue;
            }

            // Set the value, parsing it where needed
            $propertyName = $columnInfo->getPropertyName();
            $propertyValue = $columnInfo->parseDatabaseValue($valueInArray);

            // [php-7.4]: Only assign default value if it's not null, or if null is explicitly allowed
            if (($propertyValue !== null) || ($columnInfo->getIsNullable() && $propertyValue === null)) {
                $this->$propertyName = $propertyValue;
            }
        }
    }

    /**
     * Gets a key/value list of all properties that have been modified.
     *
     * @return array An array containing property values, indexed by property name.
     */
    public function getDirtyProperties(): array
    {
        $propertiesThen = $this->_trackedModelValues;
        $propertiesNow = $this->getPropertyValues();
        $propertiesDiff = [];

        foreach ($propertiesNow as $propertyName => $propertyValue) {
            if (!array_key_exists($propertyName, $propertiesThen) || $propertiesThen[$propertyName] !== $propertiesNow[$propertyName]) {
                // This property either was not previously known, or its value has changed in some way.
                $propertiesDiff[$propertyName] = $propertyValue;
            }
        }

        return $propertiesDiff;
    }

    /**
     * Gets whether this model has any "dirty" fields or not.
     *
     * @return bool If true, this model has outstanding changes that have not been committed to the database yet.
     */
    public function isDirty(): bool
    {
        return !empty($this->getDirtyProperties());
    }

    /**
     * Gets a key/value list of all columns and their values that have been modified.
     *
     * @return array An array containing property values, indexed by property name.
     */
    public function getDirtyColumns(): array
    {
        $dirtyProperties = $this->getDirtyProperties();
        $dirtyColumns = [];

        foreach ($dirtyProperties as $propertyName => $propertyValue) {
            $columnInfo = $this->getColumnForPropertyName($propertyName);

            if ($columnInfo) {
                $columnName = $columnInfo->getColumnName();
                $columnValue = $columnInfo->formatDatabaseValue($propertyValue);

                $dirtyColumns[$columnName] = $columnValue;
            }
        }

        return $dirtyColumns;
    }

    /**
     * Marks all properties as "clean" by updating all last known property values.
     */
    public function markAllPropertiesClean(): void
    {
        $this->_trackedModelValues = $this->getPropertyValues();
    }

    /**
     * Marks all properties as "dirty" by resetting all last known property values.
     */
    public function markAllPropertiesDirty(): void
    {
        $this->_trackedModelValues = [];
    }

    /**
     * Gets a list of the column names defined in this model.
     * This is translated based on the properties defined in this model.
     *
     * @return array
     */
    public function getColumnNames(): array
    {
        $propertyNames = $this->getPropertyNames();
        $columnNames = [];

        foreach ($propertyNames as $propertyName) {
            $columnNames[] = Column::getDefaultColumnName($propertyName);
        }

        return $columnNames;
    }

    /**
     * Gets the name of the primary key column.
     *
     * @return string
     */
    public function getPrimaryKeyColumnName(): string
    {
        // TODO Do this better (@annotations seem like a clever idea)
        return "id";
    }

    /**
     * Gets the name of the primary key property.
     *
     * @return string
     */
    public function getPrimaryKeyPropertyName(): string
    {
        // TODO Do this better (@annotations seem like a clever idea)
        return "id";
    }

    /**
     * Gets the value of the primary key property on this model.
     *
     * @return mixed|null
     */
    public function getPrimaryKeyValue()
    {
        $pkPropName = $this->getPrimaryKeyPropertyName();
        return $this->$pkPropName;
    }

    /**
     * Creates and returns a new query based on this model.
     *
     * @return ModelQuery
     */
    public static function query(): ModelQuery
    {
        return new ModelQuery(Instarecord::connection(), get_called_class());
    }

    /**
     * Inserts this instance a new record in the database.
     *
     * @see update()
     * @return bool
     */
    public function create(): bool
    {
        $primaryKeyName = $this->getPrimaryKeyPropertyName();

        unset($this->$primaryKeyName);

        $this->runAutoApplicator(AutoApplicator::REASON_CREATE);

        $insertPkValue = $this->query()
            ->insert()
            ->values($this->getColumnValues())
            ->executeInsert();

        // TODO Only get inserted PK value if it is known to be an auto increment column

        $this->$primaryKeyName = $insertPkValue;
        $this->markAllPropertiesClean();
        return true;
    }

    /**
     * Updates this instance's database record, based on its primary key.
     * Only "dirty" properties will be updated. If nothing was updated, this function won't do anything.
     * This function will fail if primary key is not set.
     *
     * @see create()
     * @return bool Returns whether updating the record succeeded. Also returns true if there was nothing to update.
     */
    public function update(): bool
    {
        if (!$this->isDirty()) {
            return true; // no changes
        }

        $this->runAutoApplicator(AutoApplicator::REASON_UPDATE);

        $this->query()
            ->wherePrimaryKeyMatches($this)
            ->update()
            ->set($this->getDirtyColumns())
            ->execute();

        $this->markAllPropertiesClean();
        return true;
    }

    /**
     * Deletes the record.
     *
     * @return bool Returns whether delete succeeded.
     */
    public function delete(): bool
    {
        $this->query()
            ->wherePrimaryKeyMatches($this)
            ->delete()
            ->execute();

        return true;
    }

    /**
     * Commits the updated information in this model and its' underlying relationships to the database.
     *
     * If this record does not yet have primary key information, it will cause a new record to be inserted (create).
     * Instead, if this record does already have primary key information, it will cause the record to be updated.
     * If this is an existing record, but nothing was updated, this function won't do anything but still return true.
     *
     * @see create()
     * @see update()
     * @return bool Returns whether saving the data succeeded.
     */
    public function save(): bool
    {
        $primaryKeyName = $this->getPrimaryKeyPropertyName();

        if (!empty($this->$primaryKeyName)) {
            return $this->update();
        }

        return $this->create();
    }

    /**
     * Fetches a instance of this model by its primary key value.
     *
     * @param string|int $keyValue The primary key value to seek out.
     * @return Model|$this|null Fetched model instance, or NULL if there was no result.
     */
    public static function fetch($keyValue): ?Model
    {
        $keyValue = strval($keyValue);

        $className = get_called_class();
        $referenceModel = new $className();

        /**
         * @var $referenceModel Model
         */
        $primaryKeyName = $referenceModel->getPrimaryKeyColumnName();

        return $referenceModel->query()
            ->select('*')
            ->where("{$primaryKeyName} = ?", $keyValue)
            ->querySingleModel();
    }

    /**
     * Fetches all records in the database as a collection of model instances.
     *
     * @return array
     */
    public static function all(): array
    {
        $className = get_called_class();
        $referenceModel = new $className();

        /**
         * @var $referenceModel Model
         */
        return $referenceModel->query()
            ->select('*')
            ->queryAllModels();
    }

    /**
     * Get table information.
     *
     * @return Table
     */
    public function getTableInfo(): Table
    {
        return $this->_tableInfo;
    }

    /**
     * Gets column info by its name.
     *
     * @param string $columnName
     * @return Column|null
     */
    public function getColumnByName(string $columnName): ?Column
    {
        $columnInfo = $this->_tableInfo->getColumnByName($columnName);

        if ($columnInfo) {
            return $columnInfo;
        }

        return null;
    }

    /**
     * Gets column info for a given property name.
     *
     * @param string $propertyName
     * @return Column|null
     */
    public function getColumnForPropertyName(string $propertyName): ?Column
    {
        $columnInfo = $this->_tableInfo->getColumnByPropertyName($propertyName);

        if ($columnInfo) {
            return $columnInfo;
        }

        return null;
    }

    /**
     * Converts a property name into its column name.
     *
     * @param string $propertyName
     * @return string
     */
    public function getColumnNameForPropertyName(string $propertyName)
    {
        $columnInfo = $this->getColumnForPropertyName($propertyName);

        if ($columnInfo) {
            return $columnInfo->getColumnName();
        }

        return Column::getDefaultColumnName($propertyName);
    }

    /**
     * Converts a column name into its property name.
     *
     * @param string $columnName
     * @return string
     */
    public function getPropertyNameForColumnName(string $columnName)
    {
        $columnInfo = $this->_tableInfo->getColumnByName($columnName);

        if ($columnInfo) {
            return $columnInfo->getPropertyName();
        }

        return $columnName;
    }

    /**
     * Attempts to fetch a model from the database that matches the exact values set on this instance.
     * The primary key value will be ignored when performing this transaction.
     * Only "dirty properties" will be used to prepare the query.
     *
     * @return $this|Model|null
     */
    public function fetchExisting(): ?Model
    {
        // Get all column values, ready for a query
        $columnValues = $this->getDirtyColumns();

        // Remove primary key if we have it
        $pkColName = $this->getPrimaryKeyColumnName();

        if (isset($columnValues[$pkColName])) {
            unset($columnValues[$pkColName]);
        }

        // If there is nothing to query, assume NULL (avoid presenting a default any match)
        if (empty($columnValues)) {
            return null;
        }

        // Construct the query string
        $whereStatement = "";
        $bindings = [];
        $firstCondition = true;

        foreach ($columnValues as $columnName => $columnValue) {
            if (!$firstCondition) {
                $whereStatement .= " AND ";
            }

            $whereStatement .= "`{$columnName}` = ?";
            $bindings[] = $columnValue;
            $firstCondition = false;
        }

        return self::query()
            ->where($whereStatement, ...$bindings)
            ->querySingleModel();
    }

    /**
     * Performs a "fetchExisting()", and if a result is found, assumes all properties of that instance.
     *
     * @see fetchExisting()
     * @return bool Returns TRUE if assimilation worked, FALSE otherwise.
     */
    public function tryBecomeExisting(): bool
    {
        $existingModel = $this->fetchExisting();

        if (!$existingModel) {
            return false;
        }

        $this->setColumnValues($existingModel->getColumnValues());
        return true;
    }

    /**
     * Performs any @auto hooks before committing new or changed models to the database.
     *
     * @param string $reason The change reason, e.g. "update" or "create".
     * @return bool Returns true if any data has changed.
     */
    protected function runAutoApplicator(string $reason): bool
    {
        $anyChanges = false;

        foreach ($this->getTableInfo()->getColumns() as $column) {
            if (AutoApplicator::apply($this, $column, $reason)) {
                $anyChanges = true;
            }
        }

        return $anyChanges;
    }
}