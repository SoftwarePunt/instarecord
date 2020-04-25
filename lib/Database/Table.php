<?php

namespace Instasell\Instarecord\Database;

use Instasell\Instarecord\Config\ConfigException;
use Instasell\Instarecord\Reflection\ReflectionModel;
use Instasell\Instarecord\Utils\TextTransforms;
use Minime\Annotations\Reader;

/**
 * Represents a database table, which backs a model.
 */
class Table
{
    /**
     * Reflection utility for the target Model class.
     */
    protected ReflectionModel $reflectionModel;

    /**
     * The fully qualified table class name.
     */
    private string $modelClassNameQualified;

    /**
     * The unqualified model class name.
     */
    private string $modelClassNameNoNamespace;

    /**
     * The name of the database table.
     */
    private string $tableName;

    /**
     * Indicates whether or not $columns and $columnsByName have been initialized.
     *
     * @var bool
     */
    private bool $loadedColumns;

    /**
     * Column information list.
     * Indexed by property name.
     *
     * @var Column[]
     */
    private array $columns;

    /**
     * Column information list.
     * Indexed by column name.
     *
     * @var Column[]
     */
    private array $columnsByName;

    /**
     * Table constructor.
     *
     * @param string $modelClassName Fully qualified class name for associated model.
     *
     * @throws ConfigException
     * @throws \ReflectionException
     */
    public function __construct(string $modelClassName)
    {
        $this->reflectionModel = ReflectionModel::fromClassName($modelClassName);

        $this->modelClassNameQualified = $modelClassName;
        $this->modelClassNameNoNamespace = TextTransforms::removeNamespaceFromClassName($modelClassName);

        $this->loadedColumns = false;

        // Parse class annotations to determine custom table name, etc
        $annotationReader = Reader::createFromDefaults();
        $classAnnotations = $annotationReader->getClassAnnotations($modelClassName);

        foreach ($classAnnotations as $name => $value) {
            if (strtolower($name) === "table") {
                $this->tableName = $value;
            }
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
        if ($this->loadedColumns) {
            return $this->columns;
        }

        $columns = [];
        $columnsByName = [];

        $annotationReader = Reader::createFromDefaults();

        foreach ($this->reflectionModel->getReflectionProperties() as $propertyName => $rfProp) {
            $annotationBag = $annotationReader->getPropertyAnnotations($this->modelClassNameQualified, $propertyName);
            $column = new Column($this, $propertyName, $rfProp, $annotationBag);

            $columns[$propertyName] = $column;
            $columnsByName[$column->getColumnName()] = $column;
        }

        $this->columns = $columns;
        $this->columnsByName = $columnsByName;
        $this->loadedColumns = true;

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
        if (isset($this->tableName)) {
            // Already calculated
            return $this->tableName;
        }

        // Assume default table name based on standard conventions
        $this->tableName = self::getDefaultTableName($this->modelClassNameNoNamespace);
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