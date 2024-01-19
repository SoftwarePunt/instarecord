# Relationships
You can define relationships between models using the `Relation` attribute pointing to another model.

## Defining relationships
Relationships can generally be categorized into two types:

 - **One-to-one**: A single object of type `A` is related to a single object of type `B`. For example, a `User` has a single `Profile`.
 - **One-to-many**: A single object of type `A` is related to multiple objects of type `B`. For example, a `User` has many `Posts`.

### One-to-one
You can define a one-to-one relationship by adding a field with an `Relation` attribute:

```php
<?php

use SoftwarePunt\Instarecord\Model;
use SoftwarePunt\Instarecord\Attributes\Relationship;

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
You can define a one-to-many relationship by adding a field with an `Relation` attribute:

```php
<?php

use SoftwarePunt\Instarecord\Model;
use SoftwarePunt\Instarecord\Attributes\Relationship;

class User extends Model
{
    public int $id;
    public string $name;

    /**
     * @var Post[] 
     */
    #[Relationship(Post::class)]
    public array $posts;
}
```

Because this is **an array of objects**, it is recognized as a one-to-many relationship. That means the following:
 - A backing column named `user_id` will be expected in the posts table.
 - You will usually define a corresponding `User` relationship in the `Post` model.
 - When you query this model, Instarecord can automatically load and populate the `posts` array.

Note: The `@var` annotation is not required by Instarecord, but it is recommended to help your IDE understand the type of the field.

## Loading relationships
When you query a model, you can optionally specify which relationships to load. If a relationship is not loaded, the corresponding field will remain uninitialized (meaning accessing it would cause a PHP error).

There are two loading strategies:
    
 - **Lazy loading**: Relationships are loaded on-demand, when you access the corresponding field. This means no unnecessary queries are executed, but you may end up with a lot of queries if you access many relationships.
 - **Auto loading**: After you query a model, or collection of models, Instarecord will automatically load all relationships that were not loaded yet in a combined query. This may result in fewer queries, but they may be more complex and slower. 

### Lazy loading
This is the default and fallback behavior, and requires no additional code. Simply access the field, and if it is uninitialized, Instarecord will automatically try to load it for you.
