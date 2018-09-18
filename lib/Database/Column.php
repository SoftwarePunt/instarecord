<?php

namespace Instasell\Instarecord\Database;

use DateTime;
use Exception;
use Instasell\Instarecord\Utils\TextTransforms;
use Minime\Annotations\Interfaces\AnnotationsBagInterface;

/**
 * Represents a Column within a Table.
 */
class Column
{
    const TYPE_STRING = "string";
    const TYPE_DATE_TIME = "datetime";
    const TYPE_BOOLEAN = "bool";
    const TYPE_INTEGER = "integer";
    const TYPE_DECIMAL = "decimal";
    
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
     * @var bool
     */
    protected $isNullable;

    /**
     * @var int
     */
    protected $decimals;

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
        $this->readExtraProperties();
    }

    /**
     * Internal function that parses additional column information from the annotation data.
     */
    protected function readExtraProperties(): void
    {
        if ($this->annotations->has('decimals')) {
            $this->decimals = intval($this->annotations->get('decimals'));
        }

        if (!$this->decimals) {
            $this->decimals = 4;
        }
    }

    /**
     * Determines and sets the column data type based on the "@var" annotation.
     */
    protected function determineDataType(): void
    {
        $this->dataType = self::TYPE_STRING;
        $this->isNullable = false;
        
        if ($this->annotations->has('var')) {
            $varKeywordValue = $this->annotations->get('var');
            $varKeywordValue = TextTransforms::removeNamespaceFromClassName($varKeywordValue);
            $varKeywordValue = strtolower($varKeywordValue);
            
            // Sometimes var declarations are split (e.g. "@var string|null").
            // Because a column can only be one data type, we always assume that the last value is accurate.
            $varKeywordOptions = explode('|', $varKeywordValue);
            
            foreach ($varKeywordOptions as $varKeywordOption) {
                $varKeywordOption = trim($varKeywordOption);
                
                switch ($varKeywordOption) {
                    case "\\datetime":
                    case "datetime":
                        $this->dataType = self::TYPE_DATE_TIME;
                        break;
                    case "bool":
                    case "boolean":
                        $this->dataType = self::TYPE_BOOLEAN;
                        break;
                    case "float":
                    case "double":
                    case "decimal":
                        $this->dataType = self::TYPE_DECIMAL;
                        break;
                    case "int":
                    case "integer":
                        $this->dataType = self::TYPE_INTEGER;
                        break;
                    case "null":
                        // Special case: this just indicates the column is nullable
                        $this->isNullable = true;
                        break;
                }    
            }
            
        }
        
        // If an explicit @nullable annotation exists, mark as nullable
        if ($this->annotations->has('nullable')) {
            $this->isNullable = true;
        }
    }

    /**
     * Gets whether this column is nullable or not.
     * 
     * @see determineDataType()
     * @return bool
     */
    public function getIsNullable(): bool
    {
        return $this->isNullable;
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
     * Gets the default value for this column.
     * 
     * @return mixed|null
     */
    public function getDefaultValue()
    {
        if ($this->annotations->has('default')) {
            // Explicit default value is set in "@default X" annotation, prefer this primarily
            $defaultValueExplicit = strval($this->annotations->get('default'));
            
            if ($defaultValueExplicit === "null") {
                return null;
            }
            
            return $defaultValueExplicit;
        }
        
        if ($this->getIsNullable()) {
            // This is a nullable column, no explicit default was set, so we'll set it to NULL for now
            return null;
        }
        
        // It's not a nullable column, we ought to try and set a sensible default value based on its type, if there
        // is a suitable "empty" or "zero" value for that data type.
        if ($this->dataType === self::TYPE_STRING) {
            return '';
        }
        
        if ($this->dataType === self::TYPE_INTEGER ||
            $this->dataType === self::TYPE_DECIMAL) {
            return 0;
        }
        
        if ($this->dataType === self::TYPE_BOOLEAN) {
            return false;
        }
        
        // Unable to find a suitable default, it is up to the developer to set an appropriate value before insertion
        return null;
    }

    /**
     * Formats a PHP value for database insertion according to this column's formatting rules.
     * 
     * @param mixed $input PHP value
     * @return string|null Database string for insertion
     */
    public function formatDatabaseValue($input): ?string
    {
        if ($input instanceof \DateTime) {
            return $input->format(self::DATE_TIME_FORMAT);
        }

        if ($input === true) {
            return '1';
        }

        if ($input === false) {
            return '0';
        }

        if ($this->dataType === self::TYPE_BOOLEAN) {
            if ($input === null) {
                if ($this->isNullable) {
                    return null;
                }

                return '0';
            }

            if ($input == 'false') {
                return '0';
            }

            if (is_numeric($input) && $input <= 0) {
                return '0';
            }

            if ($input) {
                return '1';
            }

            return '0';
        }

        if ($this->dataType === self::TYPE_DECIMAL) {
            return number_format(floatval($input), $this->decimals, '.', '');
        }

        if ($this->dataType === self::TYPE_INTEGER) {
            if ($input === 0 || $input === '0') {
                // (Explicit zero to prevent the "empty" check below interfering)
                return '0';
            }

            if (empty($input)) {
                // (Empty input, but non zero: blank string or NULL value)
                if ($this->isNullable) {
                    return null;
                }

                return "0";
            }

            return strval(intval($input));
        }

        if ($input === null) {
            return null;
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

        if ($this->dataType === self::TYPE_DATE_TIME) {
            if (!empty($input)) {
                // Parse attempt one: default db format
                try {
                    $dtParsed = \DateTime::createFromFormat(self::DATE_TIME_FORMAT, $input);

                    if ($dtParsed) {
                        return $dtParsed;
                    }
                } catch (Exception $ex) { }

                // Parse attempt two: alt db format (also used for "time" db fields otherwise they break)
                try {
                    $dtParsed = new DateTime($input);

                    if ($dtParsed) {
                        return $dtParsed;
                    }
                } catch (Exception $ex) { }
            }

            // Exhausted options, treat as NULL
            return null;
        }

        if ($this->dataType === self::TYPE_BOOLEAN) {
            if ($input) {
                if ($input === 'false' || $input === '0') {
                    return false;
                }

                return true;
            } else {
                return false;
            }
        }

        if ($this->dataType === self::TYPE_INTEGER) {
            return intval($input);
        }

        if ($this->dataType === self::TYPE_DECIMAL) {
            return floatval($input);
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
