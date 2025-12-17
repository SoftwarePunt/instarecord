<?php

namespace SoftwarePunt\Instarecord\Database;

use DateTime;
use DateTimeZone;
use Exception;
use ReflectionEnum;
use ReflectionType;
use ReflectionUnionType;
use SoftwarePunt\Instarecord\Attributes\FriendlyName;
use SoftwarePunt\Instarecord\Instarecord;
use SoftwarePunt\Instarecord\Model;
use SoftwarePunt\Instarecord\Relationships\Relationship;
use SoftwarePunt\Instarecord\Serialization\IDatabaseSerializable;
use SoftwarePunt\Instarecord\Tests\Samples\TestAirline;
use SoftwarePunt\Instarecord\Validation\ValidationAttribute;

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
    const TYPE_RELATIONSHIP = "relationship";

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
     * Validator attribute instances for this column.
     *
     * @var ValidationAttribute[]
     */
    protected array $validators;

    /**
     * User-friendly name for this column/property, if specified.
     * Used for error messages and any other user-facing text.
     */
    protected ?string $friendlyName;

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

        // Check property type
        if ($phpType = $rfProp->getType()) {
            if ($phpType instanceof ReflectionUnionType) {
                if ($phpType->allowsNull()) {
                    $this->isNullable = true;
                }
                $phpType = self::selectTypeFromUnion($phpType);
            }

            $phpTypeStr = $phpType->getName();

            if ($phpTypeStr) {
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
                        throw new ColumnDefinitionException("Array properties are not supported: {$rfProp->getName()}");
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
                                // DateTime handling
                                $this->dataType = self::TYPE_DATE_TIME;
                                break;
                            } else if (($classParents = class_parents($phpTypeStr)) && in_array('SoftwarePunt\Instarecord\Model', $classParents)) {
                                // Object reference to another model: One-to-one relationship
                                $this->dataType = self::TYPE_RELATIONSHIP;
                                $this->columnName = $this->columnName . "_id";
                                $this->relationshipTarget = $phpTypeStr;
                                break;
                            } else if (($classImplements = class_implements($phpTypeStr)) && in_array('SoftwarePunt\Instarecord\Serialization\IDatabaseSerializable', $classImplements)) {
                                // Object reference to a serializable object
                                $this->dataType = self::TYPE_SERIALIZED_OBJECT;
                                try {
                                    $this->referenceType = new $phpTypeStr();
                                    break;
                                } catch (Exception $ex) {
                                    throw new ColumnDefinitionException("Objects that implement IDatabaseSerializable must have a default constructor that does not throw errors, in: {$phpTypeStr}, got: {$ex->getMessage()}");
                                }
                            }
                            throw new ColumnDefinitionException("Referenced object is not a Model and not IDatabaseSerializable - not supported by Instarecord, in: {$rfProp->getName()} of type {$phpTypeStr}");
                        }
                        throw new ColumnDefinitionException("Unsupported property type encountered: {$phpTypeStr}");
                } // End of type switch
                if ($phpType instanceof \ReflectionNamedType && $phpType->allowsNull()) {
                    $this->isNullable = true;
                }
            }
        }

        $this->applyAttributeData($rfProp);
    }

    protected function applyAttributeData(\ReflectionProperty $rfProp)
    {
        $this->validators = [];
        $this->friendlyName = null;

        foreach ($rfProp->getAttributes() as $attribute) {
            $instance = $attribute->newInstance();

            if ($instance instanceof ValidationAttribute) {
                $this->validators[] = $instance;
            } else if ($instance instanceof FriendlyName) {
                $this->friendlyName = $instance->name;
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
     * Gets the user-friendly name for this column/property.
     * Derived from the property name if no friendly name was set.
     */
    public function getFriendlyName(): string
    {
        if ($this->friendlyName)
            return $this->friendlyName;

        // Fallback: camelCase -> Camel Case
        return ucwords(implode(' ', preg_split('/(?=[A-Z])/', $this->getPropertyName())));
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
        return match ($this->dataType) {
            self::TYPE_STRING => '',
            self::TYPE_INTEGER => 0,
            self::TYPE_DECIMAL => 0.0,
            self::TYPE_BOOLEAN => false,
            default => null // Unable to find suitable default, up to the developer to set appropriate value
        };
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
        return $this->dataType === self::TYPE_RELATIONSHIP;
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

    /**
     * @return ValidationAttribute[]
     */
    public function getValidators(): array
    {
        return $this->validators;
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

        if ($this->dataType === self::TYPE_RELATIONSHIP) {
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

        if ($this->getIsRelationship()) {
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
     * The normalization process takes a PHP-like $columnName and converts it to a database-standard "column_name".
     *
     * @param string $propertyName The property name from the code, to be converted to its column equivalent.
     * @return string
     */
    public static function getDefaultColumnName(string $propertyName): string
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $propertyName, $matches);

        $ret = $matches[0];

        foreach ($ret as &$match) {
            $match = ($match == strtoupper($match) ? strtolower($match) : lcfirst($match));
        }

        return implode('_', $ret);
    }

    /**
     * @throws ColumnDefinitionException Thrown if the union contains unsupported types.
     */
    public static function selectTypeFromUnion(ReflectionUnionType $unionType): ReflectionType
    {
        // Note the getTypes() result array is not sorted in order of declaration, but rather in a predetermined way:
        // https://www.php.net/manual/en/reflectionuniontype.gettypes.php#128871

        $types = $unionType->getTypes();

        // Remove "null" entries from the union type, not relevant/suitable for selection
        $types = array_filter($types, fn (ReflectionType $type) => $type->getName() !== "null");

        // Short circuit: if there's only one non-null type left, just select that type and continue as normal
        // Likely declaration was something like "string|null"
        if (count($types) === 1) {
            return reset($types);
        }

        // Pass 1: Find possible scalar types, prefer a string backing if possible
        $scalarTypes = [];
        $otherTypes = [];

        foreach ($types as $type) {
            $typeName = $type->getName();

            if ($typeName === "string") {
                // Short circuit: String is big strong and beautiful, and preferred over anything else
                return $type;
            } else if (enum_exists($typeName)) {
                // Special case: backed enums may have string backing
                $reflectionEnum = new ReflectionEnum($typeName);

                if (!$reflectionEnum->isBacked()) {
                    throw new ColumnDefinitionException("Only backed enums are supported for database serialization, tried to use: {$typeName}");
                }

                // Enums can only be backed by int or string
                $backingType = $reflectionEnum->getBackingType();
                $backingTypeName = $backingType->getName();

                if ($backingTypeName === "string") {
                    return $backingType;
                } else if ($backingTypeName === "int") {
                    $scalarTypes[] = $backingType;
                } else {
                    throw new ColumnDefinitionException("Unexpected enum backing type: {$backingTypeName}");
                }
            } else if ($typeName === "float" || $typeName === "int" || $typeName === "bool") {
                $scalarTypes[] = $type;
            } else {
                throw new ColumnDefinitionException("Unsupported type for unions in Instarecord: {$type}");
            }
        }

        // Pass 2: fall back to scalar types in preferntial order
        $tFloat = null;
        $tInt = null;
        $tBool = null;

        foreach ($scalarTypes as $scalarType) {
            $scalarName = $scalarType->getName();

            switch ($scalarName) {
                case "float":
                    $tFloat = $scalarType;
                    break;
                case "int":
                    $tInt = $scalarType;
                    break;
                case "bool":
                    $tBool = $scalarType;
                    break;
            }
        }

        if ($tFloat) return $tFloat;
        if ($tInt) return $tInt;
        if ($tBool) return $tBool;

        throw new ColumnDefinitionException("Unsupported type for unions: {$type}");
    }
}
