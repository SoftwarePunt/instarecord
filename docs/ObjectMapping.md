# Object Mapping
**Define your models as pure PHP classes with typed properties, and get instant [CRUD operations](./CRUD.md).**

## Defining models
Each model is mapped to a database table.

A model itself is a pure PHP class with typed properties that extends from the `Model` base class that Instarecord provides.

```php
<?php

use SoftwarePunt\Instarecord\Model;

class MyModel extends Model // maps to "my_models" table
{
    public string $someField; // maps to "some_field" column
}
```

Instarecord will treat any **public properties** as columns in the database table.

## Table and column names

Instarecord will translate `CamelCased` class and property names to `snake_case` for use in the database.

In the example above:
 - `MyModel` would map to the `my_models` table (pluralized and snake_cased)
 - `someField` would map to the `some_field` column (snake_cased)

### Custom table names

You can manually override the table name by adding a `TableName` attribute on the model class:

```php
<?php

use SoftwarePunt\Instarecord\Model;
use SoftwarePunt\Instarecord\Attributes\TableName;

#[TableName("custom_table_name")]
class MyModel extends Model
{
    public string $someField;
}
```

## Primary keys

⚠️ Instarecord assumes (and therefore currently **requires**) that the primary key is always an auto-incrementing integer named `id`.

## Data types

The type of each property is used to determine the column type in the database. Each type is handled differently.

Each property can also be declared nullable, which Instarecord will use to determine default and accepted values.

The following types are currently supported / mapped:

### `string`

- Default/fallback type for any untyped properties.
- String properties map to varchar or text columns.
- Default value is empty string `""`.

### `DateTime`

- Serialized in the `Y-m-d H:i:s` format. 
- Converted to/from the timezone specified in the `DatabaseConfig` (defaults to UTC). 

### `bool`

- Stored as integer value 0 or 1. 

### `int`

- Stored as an integer number without decimals.
- May be signed or unsigned; may be backed by any *int column type.

### `float`

- Stored as a decimal number.

### Enums

- Only backed enums (enums with a value type) are supported.
- When writing to the database, enums are stored as their backed enum value (column type should match).
- When reading from the database, `Enum::tryFrom()` is used. Any invalid values become `null`.

### Serialized Objects

Any object implementing `IDatabaseSerializable` can be stored as a serialized string.

This can be useful for storing as JSON, or for any other custom serialization/deserialization that can be stored as a string.

```php
<?php

use SoftwarePunt\Instarecord\Serialization\IDatabaseSerializable;

class MyCustomObject implements IDatabaseSerializable
{
    public function serializeForDatabase(): string
    {
        return json_encode($this);
    }

    public static function deserializeFromDatabase(string $serialized): self
    {
        return json_decode($serialized, true);
    }
}

class MyModel extends Model
{
    public MyCustomObject $someField;
}
```

### Relationship Objects

Any other object type is treated as a relationship object. For more details, see [Relationships](./Relationships.md).

## Union Types

Instarecord has limited support for PHP 8 union types.

Support is currently limited to `null`, scalar types, and backed enums.

When declaring a union type, Instarecord will use the broadest (most compatible) type in the union to determine how data will be serialized to/from the database.

### Union examples

#### Mixed types & backed enums

```php
public string|int|SomeEnum|null $myValue;
```

In this example, you can set any `string`, `int`, `SomeEnum` or `null` value. When writing to, or reading from, the database, the value is always coerced to a `string` or `null` value.


#### Nullable syntax

```php
public string|null $someField;
```

This will be interpreted the same as `?string` declaration with no special behavior.