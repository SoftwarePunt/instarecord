# Caching

**Caching features can help prevent duplicated queries and reduce wasted resources.**

## Static caching

Instarecord provides a basic static caching layer by default. This caching layer creates a static pool of all fetched models within the current script, allowing subsequent lookups to make use of that pool.

To configure a model for static caching, add the `CacheableModel` attribute:

```php
<?php

use SoftwarePunt\Instarecord\Model;

#[CacheableModel(ModelCacheMode::STATIC)]
class Example extends Model
{
    public int $id;
    public string $name;
}
```

**Enabling static caching for a model enables the behaviors described below:**

### Fetching by primary key

When fetching a model via primary key by using `fetch()`, the model is returned from cache if available.

The 2nd argument of `fetch()`, `$allowCached`, can be used to bypass cache on a case-by-case basis.

If there's a cache miss or bypass, the new result is added to cache.

### One-to-one relationships

When a one-to-one relationship [is declared in a model](./Relationships.md#one-to-one), the relationship load will make use of the cache whenever possible.

### Querying models / cache warmup

When using a `ModelQuery` and performing any query that returns models, those models are cached.

This can also be used to warm up the cache ahead of time.

### Deleting a model

When deleting a model via `delete()`, the static cache entry is invalidated.