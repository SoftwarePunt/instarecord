<?php

namespace Instasell\Instarecord\Database;

use Minime\Annotations\Interfaces\AnnotationsBagInterface;

/**
 * Represents a Column within a Table.
 */
class Column
{
    /**
     * The table this column is a part of.
     *
     * @var Table
     */
    protected $table;

    /**
     * The name of the property associated with this column on the model.
     *
     * @var string
     */
    protected $propertyName;

    /**
     * The name of the column in the database.
     *
     * @var string
     */
    protected $columnName;

    /**
     * A list of annotations set on this column in php code.
     *
     * @var AnnotationsBagInterface
     */
    protected $annotations;

    /**
     * Column constructor.
     *
     * @param Table $table The table this column is a part of.
     * @param string $propertyName The name of the property associated with this column on the model.
     * @param AnnotationsBagInterface $annotations Attributes extracted from annotations.
     */
    public function __construct(Table $table, string $propertyName, AnnotationsBagInterface $annotations)
    {
        $this->table = $table;
        $this->propertyName = $propertyName;
        $this->annotations = $annotations;

        $this->getColumnName();
    }

    /**
     * Gets the name of the column in the database.
     *
     * @return string
     */
    public function getColumnName(): string
    {
        if ($this->columnName) {
            // Already calculated
            return $this->columnName;
        }

        if ($this->annotations->has('column')) {
            // User defined column name
            $this->columnName = $this->annotations->get('column');
            return $this->columnName;
        }

        // Assume default column name based on standard conventions
        $this->columnName = self::getDefaultColumnName($this->propertyName);
        return $this->propertyName;
    }

    /**
     * Gets the name of this column's associated property on the model.
     *
     * @return string
     */
    public function getPropertyName(): string
    {
        return $this->propertyName;
    }

    /**
     * Given a property name, normalizes it to its column name.
     * The normalization process takes a PHP-like $columnName and converts it to a MySQL compliant "column_name".
     *
     * @param string $propertyName The property name from the code, to be converted to its column equivalent.
     * @return string
     */
    public static function getDefaultColumnName(string $propertyName)
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $propertyName, $matches);

        $ret = $matches[0];

        foreach ($ret as &$match) {
            $match = ($match == strtoupper($match) ? strtolower($match) : lcfirst($match));
        }

        return implode('_', $ret);
    }
}