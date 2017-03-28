<?php

namespace Instasell\Instarecord;

use Instasell\Instarecord\Database\ModelQuery;

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
    protected $lastKnownValues;

    /**
     * Initializes a new instance of this model which can be inserted into the database.
     * 
     * @param array|null $initialValues Optionally, an array of initial property values to set on the model.
     */
    public function __construct(?array $initialValues = null)
    {
        if ($initialValues) {
            $availablePropertyNames = $this->getPropertyNames();
            
            foreach ($initialValues as $propertyName => $propertyValue) {
                if (in_array($propertyName, $availablePropertyNames)) {
                    $this->$propertyName = $propertyValue;
                }
            }
        }
        
        $this->markAllPropertiesClean();
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
            $properties[] = $rfProperty->getName();
        }
        
        return $properties;
    }

    /**
     * Gets a key/value list of all properties and their values.
     * 
     * @return array An array containing property values, indexed by property name.
     */
    public function getProperties(): array
    {
        $propertyList = [];
        
        foreach ($this->getPropertyNames() as $propertyName) {
            $propertyList[$propertyName] = $this->$propertyName;    
        }
        
        return $propertyList;
    }

    /**
     * Gets a key/value list of all columns and their values.
     *
     * @return array An array containing property values, indexed by property name.
     */
    public function getColumns(): array
    {
        $properties = $this->getProperties();
        $columns = [];

        foreach ($properties as $propertyName => $propertyValue) {
            $columns[Table::translateColumnName($propertyName)] = $propertyValue;
        }

        return $columns;
    }

    /**
     * Gets a key/value list of all properties that have been modified.
     * 
     * @return array An array containing property values, indexed by property name.
     */
    public function getDirtyProperties(): array
    {
        $propertiesThen = $this->lastKnownValues;
        $propertiesNow = $this->getProperties();
        $propertiesDiff = [];
        
        foreach ($propertiesNow as $propertyName => $propertyValue) {
            if (!isset($propertiesThen[$propertyName]) || $propertiesThen[$propertyName] !== $propertiesNow[$propertyName]) {
                // This property either was not previously known, or its value has changed in some way.
                $propertiesDiff[$propertyName] = $propertyValue;
            }
        }
        
        return $propertiesDiff;
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
            $dirtyColumns[Table::translateColumnName($propertyName)] = $propertyValue;
        }

        return $dirtyColumns;
    }

    /**
     * Marks all properties as "clean" by updating all last known property values.
     */
    public function markAllPropertiesClean(): void
    {
        $this->lastKnownValues = $this->getProperties();
    }

    /**
     * Marks all properties as "dirty" by resetting all last known property values.
     */
    public function markAllPropertiesDirty(): void
    {
        $this->lastKnownValues = [];
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
            $columnNames[] = Table::translateColumnName($propertyName);
        }
        
        return $columnNames;
    }

    /**
     * Gets the normalized and pluralized table name for this model.
     * 
     * @return string
     */
    public function getTableName(): string
    {
        $name = Table::translateTableName(get_class($this));
        if ($name == 'models') {
            exit;
        }
        return $name;
    }

    /**
     * Gets the name of the primary key value.
     * 
     * @return string
     */
    public function getPrimaryKeyPropertyName(): string
    {
        // TODO Do this better (@annotations seem like a clever idea)
        return "id";
    }

    /**
     * Creates and returns a new query based on this model.
     * 
     * @return ModelQuery
     */
    public function query(): ModelQuery
    {
        return new ModelQuery(Instarecord::connection(), get_class($this));
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
        $this->$primaryKeyName = null;
        
        $insertPkValue = $this->query()
            ->insert()
            ->values($this->getColumns())
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
        $modifiedData = $this->getDirtyColumns();
        
        if (empty($modifiedData)) {
            return true;
        }
        
        $this->query()
            ->wherePrimaryKeyMatches($this)
            ->update()
            ->set($modifiedData)
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
}