# Querying
**Instarecord has a query builder that makes it easy to build and run more complex and dynamic queries.**

ℹ️ When you use [CRUD](./CRUD.md) operations on models, they use the query builder internally.

## Context
You can query from a **Model context** or a **Global context**.

### Model queries
You can start a model query by calling `Model::query()`.

```php
$matchingCars = Car::query()
    ->where('make = ?', 'Toyota')
    ->queryAllModels();
```

When querying from a model context, the query will default to the model's table name, and you can use convenience functions like `queryAllModels()` to map the results back to model instances.

### Global queries
You can start a global query by calling `Instarecord::query()`.

```php
$matchingCars = Instarecord::query()
    ->from('cars')
    ->where('make = ?', 'Toyota')
    ->queryAllRows();
```

When querying from a global context, you can use the `query()` function directly on the `Instarecord` class to build queries that don't belong to any specific model.

## Query functions
Chain query functions using a fluent interface to build your query.

### `->select()`
Switch to a `SELECT [..] FROM` statement and set the selection text. The selection text defaults to `*`.

You can manually set the selection text, and use bound parameters if needed:
```php
->select('make, model, year, ? AS some_value', '123')
```

### `->count()`
Switch to a `SELECT COUNT([..]) FROM` statement and set the count statement. The count statement defaults to `*`.

### `->insert()`
Switch to an `INSERT INTO` statement.

### `->insertIgnore()`
Switch to an `INSERT IGNORE INTO` statement.

### `->update()`
Switch to an `UPDATE` statement.

### `->delete()`
Switch to a `DELETE FROM` statement.

### `->from()` / `->into()`
Sets the target table name on the query.

This is already set if you are using a query from a model context, but can always be overridden.

### `->values()`
Sets the values to be inserted on an `INSERT` statement.

Pass an associative array of column names and values:

```php
->values([
    'make' => 'VW',
    'model' => 'Golf',
    'year' => 2005
])
```

### `->set()`
Sets the values to be `SET` on an `UPDATE` statement. This can be used in two ways:

1. Pass raw SQL text and use bound parameters:
```php
->set('make = ?, model = ?, year = ?',
    'VW', 'Golf', 2005)
```
2. Pass a key-value array of column names and values:
```php
->set([
    'make' => 'VW',
    'model' => 'Golf',
    'year' => 2005
])
```

### `->onDuplicateKeyUpdate()`
Adds an `ON DUPLICATE KEY UPDATE` component to the query statement. This is used for upserts (insert or update).

Pass a key-value array of column names and values to update:
```php
->onDuplicateKeyUpdate([
    'make' => 'VW',
    'model' => 'Golf',
    'year' => 2005
])
```

The second parameter is optional and specifies the primary key column name for use with `LAST_INSERT_ID()` to retrieve the inserted ID.

### `->innerJoin()`
Adds an `INNER JOIN` to the query. May use bound parameters.

### `->leftJoin()`
Adds a `LEFT JOIN` to the query. May use bound parameters.

### `->rightJoin()`
Adds a `RIGHT JOIN` to the query. May use bound parameters.

### `->where()`
Removes any previously set `WHERE` clauses and sets a new one. May use bound parameters.

### `->andWhere()`
Adds an additional `WHERE` clause to the query. May use bound parameters.

Groups multiple where blocks using `WHERE (x) AND (y) AND (z)` query syntax.

### `->having()`
Removes any previously set `HAVING` clauses and sets a new one. May use bound parameters.

### `->andHaving()` 
Adds an additional `HAVING` clause to the query. May use bound parameters.

Groups multiple having blocks using `HAVING (x) AND (y) AND (z)` query syntax.

### `->orderBy()`
Sets the `ORDER BY` statement on the query. May use bound parameters.

### `->groupBy()`
Sets the `GROUP BY` statement on the query. May use bound parameters.

### `->limit()`
Applies a `LIMIT` to the statement.

### `->offset()`
Applies an `OFFSET` to the statement. Typically used for pagination.

### `->forUpdate()`
Applies a `FOR UPDATE` to the statement. Used to lock rows for update.

Replaces any previously set locking mode.

### `->lockInShareMode()`
Applies a `LOCK IN SHARE MODE` to the statement. Used to lock rows for read.

Replaces any previously set locking mode.

## Executing queries
A query can be executed in several ways, each returning the results in a different way.

### `->execute()`
Executes the query and returns the amount of affected rows.

### `->executeInsert()`
Executes the query and attempts to return the last inserted ID.

### `->queryAllRows()`
Executes the query and returns all rows as an array of associative arrays.

### `->querySingleRow()`
Applies a `LIMIT 1` to the query and returns the first row as an associative array.

### `->querySingleValue()`
Applies a `LIMIT 1` to the query and returns the first column of the first row as a string.

### `->querySingleValueArray()`
Executes the query, fetches only the first value from each row, and combines those into an array.

### `->queryKeyValueArray()`
Executes the query, fetches the first column as the key and the second column as the value (`PDO::FETCH_KEY_PAIR`), and combines those into an associative array.

### `->queryAllModels()`
*Only available in Model query.*

Executes the query and returns all rows as an array of model instances.

### `->queryAllModelsIndexed()`
*Only available in Model query.*

Same as queryAllModels(), but indexes all options by their PK value.

### `->querySingleModel()`
*Only available in Model query.*

Uses `querySingleRow()` to fetch a single row, and returns it as a model instance.



