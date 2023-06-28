# Instarecord
**✨ The super productive database layer (ORM) for PHP with MySQL or PostgreSQL.**

[![PHPUnit](https://github.com/SoftwarePunt/instarecord/actions/workflows/phpunit.yml/badge.svg)](https://github.com/SoftwarePunt/instarecord/actions/workflows/phpunit.yml)

## How it works
With Instarecord you define your models in pure classes:

```php
<?php

use SoftwarePunt\Instarecord\Model;

/**
 * This fully automatic model will refer to a "users" table with "id", "email" and "name" columns.
 */
class User extends Model {
    public int $id;
    public string $email;
    public ?string $name;
}
```

Finding, creating and updating records is a breeze:

```php
// Insert a new record
$user = new User();
$user->email = "bobby@sample.web";
$user->save(); 

// Auto incremented primary key
echo "Created user #{$user->id}!";

// Look up a user with a prepared statement
$user = User:::query()
    ->where('email = ?', "bobby@sample.web")
    ->querySingleModel();

// Update the user
$user->name = "Bobby";
$user->save();
```
