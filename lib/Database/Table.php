<?php

namespace Softwarepunt\Instarecord\Database;

use Minime\Annotations\Reader;
use Softwarepunt\Instarecord\Config\ConfigException;
use Softwarepunt\Instarecord\Reflection\ReflectionModel;
use Softwarepunt\Instarecord\Utils\TextTransforms;

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

        foreach ($this->reflectionModel->getReflectionProperties() as $propertyName => $rfProp) {
            $column = new Column($this, $propertyName, $rfProp,
                $this->reflectionModel->getPropertyDefaultValue($propertyName));

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