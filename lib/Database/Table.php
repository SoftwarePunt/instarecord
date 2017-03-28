<?php

namespace Instasell\Instarecord\Database;

use Instasell\Instarecord\Config\ConfigException;
use Instasell\Instarecord\Config\ModelConfig;
use Instasell\Instarecord\Model;
use Instasell\Instarecord\Utils\TextTransforms;
use Minime\Annotations\Reader;

/**
 * Represents a database table, which backs a model.
 */
class Table
{
    /**
     * The fully qualified table class name.
     *
     * @var string
     */
    private $modelClassNameQualified;

    /**
     * The unqualified model class name.
     *
     * @var string
     */
    private $modelClassName;

    /**
     * A dummy instance of the model being referenced.
     *
     * @var Model
     */
    private $referenceModel;

    /**
     * The name of the table.
     *
     * @var string
     */
    private $tableName;

    /**
     * Column information list.
     * Indexed by property name.
     *
     * @var Column[]
     */
    private $columns;

    /**
     * Column information list.
     * Indexed by column name.
     *
     * @var
     */
    private $columnsByName;

    /**
     * Table constructor.
     *
     * @param string $modelClassName Fully qualified class name for associated model.
     */
    public function __construct(string $modelClassName)
    {
        if (!class_exists($modelClassName)) {
            throw new ConfigException("Cannot determine table information for invalid class name: {$modelClassName}");
        }

        $this->modelClassNameQualified = $modelClassName;
        $this->modelClassName = TextTransforms::removeNamespaceFromClassName($modelClassName);
        $this->referenceModel = (new \ReflectionClass($modelClassName))->newInstanceWithoutConstructor();

        if (!$this->referenceModel instanceof Model) {
            throw new ConfigException("Cannot determine table information for class that does not extend from Model: {$modelClassName}");
        }
    }

    /**
     * Returns the column information.
     * Iterates the property members in the model class to extract column configuration data.
     *
     * @return Column[]
     */
    public function getColumns(): array
    {
        if ($this->columns) {
            return $this->columns;
        }

        $columns = [];
        $columnsByName = [];

        $allPropertyNames = $this->referenceModel->getPropertyNames();
        $annotationReader = Reader::createFromDefaults();

        foreach ($allPropertyNames as $propertyName) {
            $annotations = $annotationReader->getPropertyAnnotations($this->modelClassNameQualified, $propertyName);
            $column = new Column($this, $propertyName, $annotations);

            $columns[$propertyName] = $column;
            $columnsByName[$column->getColumnName()] = $column;
        }

        $this->columns = $columns;
        $this->columnsByName = $columnsByName;
        return $columns;
    }

    /**
     * Gets column information by property name.
     *
     * @param string $propertyName
     * @return Column|null
     */
    public function getColumnByPropertyName(string $propertyName): ?Column
    {
        if (empty($this->columns)) {
            $this->getColumns();
        }

        if (isset($this->columns[$propertyName])) {
            return $this->columns[$propertyName];
        }

        return null;
    }

    /**
     * Gets column information by column name.
     *
     * @param string $columnName
     * @return Column|null
     */
    public function getColumnByName(string $columnName): ?Column
    {
        if (empty($this->columnsByName)) {
            $this->getColumns();
        }

        if (isset($this->columnsByName[$columnName])) {
            return $this->columnsByName[$columnName];
        }

        return null;
    }

    /**
     * Gets the table name.
     *
     * @return string
     */
    public function getTableName(): string
    {
        if ($this->tableName) {
            // Already calculated
            return $this->tableName;
        }

        // Assume default table name based on standard conventions
        $this->tableName = self::getDefaultTableName($this->modelClassName);
        return $this->tableName;
    }

    /**
     * @var array
     */
    public static $tableInfoCache = [];

    /**
     * @param string $modelClassName
     * @return Table
     */
    public static function getTableInfo(string $modelClassName): Table
    {
        if (!isset(self::$tableInfoCache[$modelClassName])) {
            self::$tableInfoCache[$modelClassName] = new Table($modelClassName);
        }

        return self::$tableInfoCache[$modelClassName];
    }

    /**
     * Based on a Model name, returns the table name.
     *
     * @param string $modelClassName
     * @return string
     */
    public static function getTableNameForClass(string $modelClassName): string
    {
        $tableInfo = Table::getTableInfo($modelClassName);
        return $tableInfo->getTableName();
    }

    /**
     * Gets the "default" table name.
     *
     * @param string $className
     * @return mixed|string
     */
    public static function getDefaultTableName(string $className)
    {
        $tableName = TextTransforms::removeNamespaceFromClassName($className);
        $tableName = TextTransforms::pluralize($tableName);
        $tableName = Column::getDefaultColumnName($tableName);

        return $tableName;
    }
}