# CRUD
**Once your [models are set up](./ObjectMapping.md), you can immediately use CRUD (create, update, delete) operations on them with.**

## Creating records
Initialize a model instance, set its properties, and call `save()` to insert it into the database:

```php
$car = new Car();
$car->make = "Toyota";
$car->model = "Corolla";
$car->year = 2005;
$car->save(); // INSERT INTO cars [..]
```

## Fetching by ID
Load a record by its primary key:

```php
$car = Car::find(123); // SELECT * FROM cars WHERE id = 123
```

## Updating records
Modify a model instance and call `save()` to update it in the database:

```php
$car = Car::find(123);
$car->year = 2006;
$car->save(); // UPDATE cars SET year = 2006 WHERE id = 123
```

Calling `save()` will only update the properties that have changed.

## Upserting records
Upsert allows you to update an existing record, or insert a new one if it doesn't exist:

```php
$car = new Car();
$car->make = "VW";
$car->model = "Golf";
$car->year = 2005;
$car->upsert(); // INSERT INTO cars [..] ON DUPLICATE KEY UPDATE [..]
``` 

Calling `upsert()` will insert or update *all* values (not just dirty ones).

## Deleting records
Call `delete()` on a model instance to remove it from the database:

```php
$car = Car::find(123);
$car->delete(); // DELETE FROM cars WHERE id = 123
```

## Querying records
Use the `query()` method to build complex queries:

```php
$matchingCars = Car::query()
    ->where('make = ?', 'Toyota')
    ->queryAllModels();
```

See the [Queries](./Queries.md) documentation for more details on query building.

## Fetch matching existing record
Use the `fetchExisting()` method to quickly fetch any model with identical values:

```php
$car = new Car();
$car->make = "Toyota";
$car->model = "Corolla";
$car->year = 2005;

if ($existingCar = $car->fetchExisting()) {
    echo "Found matching existing car with ID: " . $existingCar->id;
}
```

You can even use `tryBecomeExisting()` to assume the properties and ID of an existing record if one can be found.