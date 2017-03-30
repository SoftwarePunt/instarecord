<?php

namespace Instasell\Instarecord\Database;

use Instasell\Instarecord\Utils\TextTransforms;
use Minime\Annotations\Interfaces\AnnotationsBagInterface;

/**
 * Represents a Column within a Table.
 */
class Column
{
    const TYPE_STRING = "string";
    const TYPE_DATE_TIME = "datetime";
    
    const DATE_TIME_FORMAT = "Y-m-d H:i:s";
    
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
     * The data type of the column.
     * 
     * @var string
     */
    protected $dataType;

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
        
        $this->determineDataType();
        $this->getColumnName();
    }

    /**
     * Determines and sets the column data type based on the "@var" annotation.
     */
    protected function determineDataType(): void
    {
        $this->dataType = self::TYPE_STRING;
        
        if ($this->annotations->has('var')) {
            $varKeyword = $this->annotations->get('var');
            $varKeyword = TextTransforms::removeNamespaceFromClassName($varKeyword);
            $varKeyword = strtolower($varKeyword);
            
            switch ($varKeyword) {
                case "datetime":
                    $this->dataType = self::TYPE_DATE_TIME;
                    break;
            }
        }
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
     * Formats a PHP value for database insertion according to this column's formatting rules.
     * 
     * @param mixed $input PHP value
     * @return string|null Database string for insertion
     */
    public function formatDatabaseValue($input): ?string
    {
        if ($input === null) {
            return null;
        }

        if ($input instanceof \DateTime) {
            return $input->format(self::DATE_TIME_FORMAT);
        }
        
        return strval($input);
    }

    /**
     * Parses a value from the database to PHP format according to this column's formatting rules.
     * 
     * @param string|null $input Database value, string retrieved from data row
     * @return mixed PHP value
     */
    public function parseDatabaseValue(?string $input)
    {
        if ($input === null) {
            return null;
        }

        if ($this->dataType == self::TYPE_DATE_TIME) {
            return \DateTime::createFromFormat(self::DATE_TIME_FORMAT, $input);
        }
        
        return strval($input);
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