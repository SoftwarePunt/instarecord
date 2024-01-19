# Migrations
When you define models in Instarecord, you can also use them to automatically generate and run database migrations.

That way, you can keep your database schema in sync with your models without having to write any SQL.

## Defining your models
You define your models as usual - they must inherit from `Model` and contain some fields that translate to database columns.

### Default assumptions
Instarecord makes the following assumptions by default, and you cannot currently change them:

 - 🔑 `id` is the primary key, and is an auto-incrementing unsigned integer
 - 🔤 Column names are `snake_case` versions of the field names (e.g. `firstName` becomes `first_name`)

### Defining foreign keys
You can define foreign keys by adding a field with an `Relation` attribute:

```php
<?php

use SoftwarePunt\Instarecord\Model;
use SoftwarePunt\Instarecord\Attributes\Relation;

class User extends Model
{
    public int $id;
    public string $name;

    #[Relation(Organization::class)]
    public Organisation $organization;

    /**
     * @var Post[] 
     */
    #[Relation(Post::class)]
    public array $posts;
}
```

The example above will automatically infer the type of relationship from the type of the field:

👉 `Organization` is a single object, which makes it a "belongs to" relationship. It will be backed by an `organization_id` column, with a foreign key pointing to the `Organization` model's primary key (`id`).

👉 `Post[]` is an array of objects, which makes it a "has many" relationship. The `Post` class should have a corresponding `user_id` column or `User` relationship, with a foreign key pointing to the `User` model's primary key (`id`).