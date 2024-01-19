# Relationships
You can define relationships between models to enable automatic loading of related data.

It's an optional feature; you can use Instarecord without relationships if you want to. This will give you greater control over the queries you execute, and can be more efficient if you don't need the related data.

## Defining relationships
Relationships can generally be categorized into two types:

 - **One-to-one**: A single object of type `A` is related to a single object of type `B`. For example, a `User` has a single `Profile`.
 - **One-to-many**: A single object of type `A` is related to multiple objects of type `B`. For example, a `User` has many `Posts`.

**Many-to-many** relationships are implicitly supported; simply define a model that represents the connecting table and define two one-to-many relationships.

### One-to-one
You can define an X-to-one relationship by simply adding a field that references another model:

```php
<?php

use SoftwarePunt\Instarecord\Model;

class User extends Model
{
    public int $id;
    public string $name;
    public Profile $profile; // profile_id
}
```

The backing column will be determined automatically. In this example, the `profile` property will be backed by a column named `profile_id`. 

Once you've defined the relationship, eager loading will be used automatically. In this example, when you query a `User`, the related `Profile` will be loaded and populated automatically.

### One-to-many
...coming soon...

## Loading relationships

### Eager loading by default
When you query a model, or collection of models, all relationships are loaded automatically. This is called **eager loading**.

Eager loading will always cause extra queries to be executed, and can be quite inefficient if you don't need the relationships or have a lot of data.

If you use `queryAllModels()`, Instarecord will batch the queries to load all relationships at once. If you `fetch()` or otherwise load a single model, Instarecord will load the relationships one-by-one.