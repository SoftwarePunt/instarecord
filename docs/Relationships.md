# Relationships
You can define relationships between models using the `Relationship` attribute pointing to another model.

## Defining relationships
Relationships can generally be categorized into two types:

 - **One-to-one**: A single object of type `A` is related to a single object of type `B`. For example, a `User` has a single `Profile`.
 - **One-to-many**: A single object of type `A` is related to multiple objects of type `B`. For example, a `User` has many `Posts`.

### One-to-one
You can define a one-to-one relationship by adding a field with an `Relationship` attribute:

```php
<?php

use SoftwarePunt\Instarecord\Model;
use SoftwarePunt\Instarecord\Relationships\Relationship;

class User extends Model
{
    public int $id;
    
    public string $name;
    
    #[Relationship(Profile::class)]
    public Profile $profile;
}
```

Because this is **a single object**, it is recognized as a one-way relationship. That means the following:
 - A backing column named `profile_id` will be expected in the users table (if you use migrations, this will be created automatically).
 - When you query this model, Instarecord can automatically load and populate the `profile` field.

### One-to-many
Coming soon because we can do better (WIP).

## Loading relationships

### Eager loading by default
When you query a model, or collection of models, all relationships are loaded automatically. This is called **eager loading**.

This will cause extra queries to be executed, and can be quite inefficient if you don't need the relationships or have a lot of data.

Additionally, Instarecord does not currently batch these queries so it's quite awful for performance (WIP).