# Relationships
You can define relationships between models to enable automatic loading of related data.

It's an optional feature; you can use Instarecord without relationships if you want to. This will give you greater control over the queries you execute, and can be more efficient if you don't need the related data.

## Defining relationships
Relationships can generally be categorized into two types:

 - **One-to-one**: A single object of type `A` is related to a single object of type `B`. For example, a `User` has a single `Profile`.
 - **One-to-many**: A single object of type `A` is related to multiple objects of type `B`. For example, a `User` has many `Posts`.

**Many-to-many** relationships are implicitly supported; simply define a model that represents the connecting table and define two one-to-many relationships.

## One-to-one
You can define a one-to-one relationship by simply adding a field that references another model:

```php
<?php

use SoftwarePunt\Instarecord\Model;

class User extends Model
{
    public int $id;
    public string $name;
    public Profile $profile; // will be backed by `profile_id`
}
```

The backing column will be determined automatically. In this example, the `profile` property will be backed by a column named `profile_id`. 

Once you've defined the relationship, eager loading will be used automatically. In this example, when you query a `User`, the related `Profile` will be loaded and populated automatically.

### Eager loading
When you query a model (or collection of models) with a one-to-one relationship, these are loaded automatically. This is called **eager loading**.

Eager loading will always cause extra queries to be executed, and can be quite inefficient if you don't need the relationships or have a lot of data.

If you use `queryAllModels()`, Instarecord will batch the queries to load all relationships at once. If you `fetch()` or otherwise load a single model, Instarecord will load the relationships one-by-one.

## One-to-many
A `ManyRelationship` utility is provided that allows you to more easily work with one-to-many relationships. It provides a cached, lazy-loaded wrapper around the collection that can handle the boring queries for you.

```php
<?php

use SoftwarePunt\Instarecord\Model;

class User extends Model
{
    public int $id;
    public string $name;
    
    public function posts(): ManyRelationship
    {
        return $this->hasMany(Post::class);
    }
}
```

Rather than defining a property, you define a method that returns a `ManyRelationship` instance. This instance will be cached and reused for the lifetime of the model.

You can optionally specify a foreign key column name as the second parameter to `hasMany()`. Otherwise, it is automatically derived from the singularized host table name with an `_id` suffix (so, in this example, `user_id`).

### Querying
You can use the `query()` method on the relationship to get a query builder for the related model:

```php
$user->posts()->query(); // ModelQuery<Post>
```

By default, the query results will be hooked. This means when your query loads any models, they are automatically added to the relationship's cache. 

### Fetch all
You can use the `all()` method on the relationship to get an array of all results:

```php
$user->posts()->all(); // Post[]
```

This may cause a large query, although only records that are not already cached will be loaded. 

This list will be cached on the relationship, so subsequent calls will not cause additional queries to be executed.

### Fetch one
You can use the `fetch()` method on the relationship to get a single result:

```php
$user->posts()->fetch(123); // Post|null
```

If the post is already cached (e.g. by an earlier `all()` call or query), 

### Cache management
You can invalidate and clear the cache for a relationship by calling `reset()`:

```php
$user->posts()->reset();
```

You can also manually add items to the cache:

```php
$user->posts()->addLoaded($post);
$user->posts()->addLoadedArray($posts);
```