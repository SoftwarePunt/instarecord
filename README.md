# Instarecord
**✨ A nice PHP ORM for MySQL.**

[![PHPUnit](https://github.com/SoftwarePunt/instarecord/actions/workflows/phpunit.yml/badge.svg)](https://github.com/SoftwarePunt/instarecord/actions/workflows/phpunit.yml)

*This library is currently used for some internal projects. Extended docs coming soon. Not recommended for production usage yet.*

## The pitch
🧙‍♂️ You define your models with typed variables, and Instarecord figures out the rest, like magic.

```php
<?php

class User extends Model {
    public int $id;
    public string $email;
    public ?string $name;
}

$user = new User();
$user->email = "bob@web.net";
$user->save(); 

echo "Created user #{$user->id}!";
```
