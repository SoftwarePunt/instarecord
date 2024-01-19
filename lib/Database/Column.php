<?php

namespace SoftwarePunt\Instarecord\Database;

use DateTime;
use DateTimeZone;
use Exception;
use SoftwarePunt\Instarecord\Instarecord;
use SoftwarePunt\Instarecord\Model;
use SoftwarePunt\Instarecord\Relationships\Relationship;
use SoftwarePunt\Instarecord\Serialization\IDatabaseSerializable;
use SoftwarePunt\Instarecord\Tests\Samples\TestAirline;

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
    const TYPE_ENUM = "enum";
    const TYPE_SERIALIZED_OBJECT = "serialized";
    const TYPE_RELATIONSHIP_ONE = "rel_one";
    const TYPE_RELATIONSHIP_MANY = "rel_many";

    const DATE_TIME_FORMAT = "Y-m-d H:i:s";
    const DEFAULT_TIMEZONE = "UTC";

    const AUTO_MODE_CREATED = "created";
    const AUTO_MODE_MODIFIED = "modified";

    /**
     * The table this column is a part of.
     */
    protected Table $table;

    /**
     * The name of the property associated with this column on the model.
     */
    protected string $propertyName;

    /**
     * The name of the column in the database.
     */
    protected string $columnName;

    /**
     * @var mixed|null
     */
    protected $defaultValue;

    /**
     * The data type of the column.
     */
    protected string $dataType;

    /**
     * For TYPE_SERIALIZED_OBJECT:
     * An empty / default instance of the IDatabaseSerializable class referenced by this column's type.
     * This can be cloned and filled (dbUnserialize) as needed when reading from the database.
     */
    protected ?IDatabaseSerializable $referenceType;

    /**
     * For TYPE_ENUM:
     * Reflected enum type used for this column.
     * Only backed enums (int/string) are supported.
     */
    protected ?\ReflectionEnum $reflectionEnum;

    /**
     * The target class for the relationship, if this is a relationship column.
     */
    protected ?string $relationshipTarget;

    /**
     * Indicates whether this column supports NULL values or not.
     */
    protected bool $isNullable;

    /**
     * Automatic fill mode for this column.
     *
     * @see Column::AUTO_MODE_*
     */
    protected ?string $autoMode;

    /**
     * The timezone to use for parsing/formatting date/time/datetime values.
     */
    protected DateTimeZone $timezone;

    /**
     * Column constructor.
     *
     * @param Table $table The table this column is a part of.
     * @param string $propertyName The name of the property associated with this column on the model.
     * @param \ReflectionProperty|null $rfProp Property reflection data.
     * @param mixed|null $defaultValue
     *
     * @throws ColumnDefinitionException
     */
    public function __construct(Table $table, string $propertyName, ?\ReflectionProperty $rfProp, $defaultValue = null)
    {
        $this->table = $table;
        $this->propertyName = $propertyName;
        $this->columnName = self::getDefaultColumnName($this->propertyName);
        $this->defaultValue = $defaultValue;

        $this->applyReflectionData($rfProp); // apply type + nullable data / set defaults

        $this->timezone = new DateTimeZone(
            Instarecord::config()->timezone
        );
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Init

    /**
     * Determines and sets the column data type, relationships and misc data based on its field definition.
     *
     * @param \ReflectionProperty|null $rfProp Property reflection data.
     * @throws ColumnDefinitionException
     */
    protected function applyReflectionData(?\ReflectionProperty $rfProp): void
    {
        $this->dataType = self::TYPE_STRING;
        $this->referenceType = null;
        $this->reflectionEnum = null;
        $this->relationshipTarget = null;
        $this->isNullable = false;

        if (!$rfProp)
            // May be null in test scenarios, leave non-nullable string default
            return;

        // Check relationship attribute
        /**
         * @var $relationshipAttr Relationship|null
         */
        $relationshipAttr = ($rfProp->getAttributes(Relationship::class)[0] ?? null)?->newInstance();

        // Check property type
        if ($phpType = $rfProp->getType()) {
            if ($phpTypeStr = $phpType->getName()) {
                switch ($phpTypeStr) {
                    case "bool":
                        $this->dataType = self::TYPE_BOOLEAN;
                        break;
                    case "int":
                        $this->dataType = self::TYPE_INTEGER;
                        break;
                    case "float":
                        $this->dataType = self::TYPE_DECIMAL;
                        break;
                    case "string":
                        $this->dataType = self::TYPE_STRING;
                        break;
                    case "array":
                        if ($relationshipAttr) {
                            $this->dataType = self::TYPE_RELATIONSHIP_MANY;
                            $this->columnName = $relationshipAttr->columnName ?? ($this->columnName . "_id");
                            $this->relationshipTarget = $relationshipAttr->modelClass;
                        } else {
                            throw new ColumnDefinitionException("Array properties are not supported without a relationship attribute: {$rfProp->getName()}");
                        }
                        break;
                    default:
                        if (enum_exists($phpTypeStr)) {
                            $this->dataType = self::TYPE_ENUM;
                            $this->reflectionEnum = new \ReflectionEnum($phpTypeStr);
                            if (!$this->reflectionEnum->isBacked()) {
                                throw new ColumnDefinitionException("Only backed enums are supported for database serialization, tried to use: {$phpTypeStr}");
                            }
                            break;
                        } else if (class_exists($phpTypeStr)) {
                            if ($phpTypeStr === "DateTime" || $phpTypeStr === "\DateTime") {
                                $this->dataType = self::TYPE_DATE_TIME;
                                break;
                            } else {
                                if ($relationshipAttr) {
                                    $this->dataType = self::TYPE_RELATIONSHIP_ONE;
                                    $this->columnName = $relationshipAttr->columnName ?? ($this->columnName . "_id");
                                    $this->relationshipTarget = $relationshipAttr->modelClass;
                                    break;
                                }
                                if ($classImplements = class_implements($phpTypeStr)) {
                                    if (in_array('SoftwarePunt\Instarecord\Serialization\IDatabaseSerializable', $classImplements)) {
                                        $this->dataType = self::TYPE_SERIALIZED_OBJECT;
                                        try {
                                            $this->referenceType = new $phpTypeStr();
                                            break;
                                        } catch (Exception $ex) {
                                            throw new ColumnDefinitionException("Objects that implement IDatabaseSerializable must have a default constructor that does not throw errors, in: {$phpTypeStr}, got: {$ex->getMessage()}");
                                        }
                                    }
                                }
                                throw new ColumnDefinitionException("Object property types must implement IDatabaseSerializable or have a Relationship attribute, in: {$rfProp->getName()} of type {$phpTypeStr}");
                            }
                        }
                        throw new ColumnDefinitionException("Unsupported property type encountered: {$phpTypeStr}");
                } // End of type switch
                if ($phpType instanceof \ReflectionNamedType && $phpType->allowsNull()) {
                    $this->isNullable = true;
                }
            }
        }
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Getters

    /**
     * Gets whether this column is nullable or not.
     *
     * @return bool
     * @see applyReflectionData()
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

        // Assume default column name based on standard conventions
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
        if ($this->defaultValue) {
            return $this->defaultValue;
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
     * Gets the column data type.
     *
     * @return string
     * @see Column::TYPE_*
     */
    public function getType(): string
    {
        return $this->dataType;
    }

    public function getDecimals(): int
    {
        switch ($this->dataType) {
            case self::TYPE_DECIMAL:
                return 4;
        }
        return 0;
    }

    /**
     * Gets the "auto mode" for this column, based on its name and type.
     */
    public function getAutoMode(): ?string
    {
        if ($this->dataType === self::TYPE_DATE_TIME) {
            // Auto DateTime columns
            if ($this->columnName === "created_at") {
                return self::AUTO_MODE_CREATED;
            } else if ($this->columnName === "modified_at" || $this->columnName === "updated_at") {
                return self::AUTO_MODE_MODIFIED;
            }
        }
        return null;
    }

    /**
     * Gets whether this is an automatically managed column.
     */
    public function hasAuto(): bool
    {
        return !!$this->getAutoMode();
    }

    /**
     * Gets whether this column is a virtual relationship column.
     */
    public function getIsRelationship(): bool
    {
        return $this->dataType === self::TYPE_RELATIONSHIP_ONE
            || $this->dataType === self::TYPE_RELATIONSHIP_MANY;
    }

    /**
     * Gets whether this column is a virtual relationship column, specifically a one-to-one relationship.
     */
    public function getIsOneRelationship(): bool
    {
        return $this->dataType === self::TYPE_RELATIONSHIP_ONE;
    }

    /**
     * Gets whether this column is a virtual relationship column, specifically an array of foreign objects.
     */
    public function getIsManyRelationship(): bool
    {
        return $this->dataType === self::TYPE_RELATIONSHIP_MANY;
    }

    /**
     * Gets the target class for the relationship, if this is a relationship column.
     */
    public function getRelationshipClass(): ?string
    {
        return $this->relationshipTarget;
    }

    public function getRelationshipReference(): Model
    {
        $targetClass = $this->relationshipTarget;
        if (!$targetClass) {
            throw new \LogicException("Attempted to get a relationship reference for a column that has no defined relationship: {$this->columnName}");
        }

        $targetRef = new $targetClass();
        if (!($targetRef instanceof Model)) {
            throw new \LogicException("Attempted to load a relationship that is not a model: {$targetClass}");
        }

        return $targetRef;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Database logic

    /**
     * Formats a PHP value for database insertion according to this column's formatting rules.
     *
     * @param mixed $input PHP value
     * @return string|null Database string for insertion
     */
    public function formatDatabaseValue(mixed $input): ?string
    {
        if (is_object($input)) {
            if ($input instanceof \DateTime) {
                $input->setTimezone($this->timezone);
                return $input->format(self::DATE_TIME_FORMAT);
            }
            if ($input instanceof IDatabaseSerializable) {
                return $input->dbSerialize();
            }
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
            return number_format(floatval($input), $this->getDecimals(), '.', '');
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

        if ($input instanceof \BackedEnum) {
            return $input->value;
        }

        if ($input === null) {
            return null;
        }

        if ($this->dataType === self::TYPE_RELATIONSHIP_ONE) {
            /**
             * @var $input Model
             */
            return $input->getPrimaryKeyValue();
        }

        return strval($input);
    }

    /**
     * Parses a value from the database to PHP format according to this column's formatting rules.
     *
     * @param string|null $input Database value, string retrieved from data row
     * @param bool $loadRelationships If true, automatically load defined relationships (causing additional queries).
     * @return mixed PHP value
     */
    public function parseDatabaseValue(?string $input, bool $loadRelationships = false): mixed
    {
        if ($input === null) {
            return null;
        }

        if ($this->dataType === self::TYPE_DATE_TIME) {
            if (!empty($input)) {
                // Parse attempt one: default db format
                try {
                    $dtParsed = \DateTime::createFromFormat(self::DATE_TIME_FORMAT, $input, $this->timezone);

                    if ($dtParsed) {
                        return $dtParsed;
                    }
                } catch (Exception $ex) {
                }

                // Parse attempt two: alt db format (also used for "time" db fields otherwise they break)
                try {
                    $dtParsed = new DateTime($input, $this->timezone);

                    if ($dtParsed) {
                        return $dtParsed;
                    }
                } catch (Exception $ex) {
                }
            }

            // Exhausted options, treat as NULL
            return null;
        }

        if ($this->dataType === self::TYPE_ENUM) {
            // Call Enum::tryFrom(), which will return the case object or null
            return forward_static_call([$this->reflectionEnum->name, 'tryFrom'], $input);
        }

        if ($this->dataType === self::TYPE_SERIALIZED_OBJECT) {
            $nextInstance = clone $this->referenceType;
            $nextInstance->dbUnserialize($input);
            return $nextInstance;
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

        if ($this->getIsOneRelationship()) {
            if ($loadRelationships) {
                return $this->getRelationshipReference()::fetch($input);
            } else {
                return null;
            }
        }

        return strval($input);
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Util

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
