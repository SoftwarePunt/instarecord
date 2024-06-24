# Instarecord
**✨ A hyper productive ORM for PHP.**

[![PHPUnit](https://github.com/SoftwarePunt/instarecord/actions/workflows/phpunit.yml/badge.svg)](https://github.com/SoftwarePunt/instarecord/actions/workflows/phpunit.yml)

Instarecord makes it super easy and fun to work with MySQL databases in PHP. It's fast and intuitive, and loaded with optional features to make your life easier.

## The pitch
🧙‍♂️ Define your models with typed variables, and Instarecord figures out the rest!

```php
<?php

class User extends Model
{
    public int $id;
    public string $email;
    public ?string $name;
}

$user = new User();
$user->email = "bob@web.net";
$user->save(); 

echo "Created user #{$user->id}!";
```

## Features

### 🤝 [Relationships](./docs/Relationships.md)
Define relationships between your models and easily load them in an optimized way.

### ✅ [Validation](./docs/Validation.md)
Add constraints to your model properties and validate them with user-friendly error messages. 

## Quickstart

### Installation
Add Instarecord to your project with [Composer](https://getcomposer.org/):

```bash
composer require softwarepunt/instarecord
```

### Configuration
Pass your own `DatabaseConfig` or modify the default one:

```php
<?php

use SoftwarePunt\Instarecord\Instarecord;

$config = Instarecord::config();
$config->charset = "utf8mb4";
$config->unix_socket = "/var/run/mysqld/mysqld.sock";
$config->username = "my_user";
$config->password = "my_password";
$config->database = "my_database";
$config->timezone = "UTC";
```  

### Use models
Defines your models by creating normal classes with public properties, and extending `Model`:

```php
<?php

use SoftwarePunt\Instarecord\Model;

class Car extends Model
{
    public int $id;
    public string $make;
    public string $model;
    public int $year;
}
```

Now you can create, read, update, and delete records with ease:

```php
$car = new Car();
$car->make = "Toyota";
$car->model = "Corolla";
$car->year = 2005;
$car->save(); // INSERT INTO cars [..]

// Post insert, the primary key (id) is automatically populated

$car->year = 2006;
$car->save(); // UPDATE cars SET year = 2006 WHERE id = 123

$car->delete(); // DELETE FROM cars WHERE id = 123
```

### Run queries
You can easily build and run custom queries, and get results in various ways - from raw data to fully populated models.

#### From models
```php
$matchingCars = Car::query()
    ->where('make = ?', 'Toyota')
    ->andWhere('year > ?', 2000)
    ->orderBy('year DESC')
    ->limit(10)
    ->queryAllModels(); // Car[]
```

#### Standalone

```php
$carsPerYear = Instarecord::query()
    ->select('year, COUNT(*) as count')
    ->from('cars')
    ->groupBy('year')
    ->queryKeyValueArray(); // [2005 => 10, 2006 => 5, ..]
```