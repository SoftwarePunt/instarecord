<?php

namespace SoftwarePunt\Instarecord\Caching;

use SoftwarePunt\Instarecord\Model;

/**
 * Interface for a model cache provider implementation.
 */
interface IModelCache
{
    // -----------------------------------------------------------------------------------------------------------------
    // Event hooks (pre)

    /**
     * Cache hook: a model is about to be fetched from the database by its primary key value.
     *
     * @param Model $model Empty reference instance for the model being fetched.
     * @param int|string $pkVal The primary key value being fetched.
     * @return Model|null If a cached model is available, its instance should be returned. If null, fetch will go ahead.
     */
    public function beforeModelFetch(Model $model, int|string $pkVal): ?Model;

    // -----------------------------------------------------------------------------------------------------------------
    // Event hooks (post)

    /**
     * Cache event handler: a model was successfully saved.
     * Its cache entry may need to be added, updated or invalidated.
     *
     * @param Model $model The model instance that was just saved.
     */
    public function onModelSaved(Model $model): void;

    /**
     * Cache event handler: a model was fetched from the database.
     * Its cache entry may need to be added, updated or invalidated.
     *
     * @param Model $model The model instance that was just fetched.
     */
    public function onModelFetched(Model $model): void;

    /**
     * Cache event handler: a model was deleted from the database.
     * Its cache entry may need to be removed or invalidated.
     *
     * @param Model $model The model instance that was just deleted.
     */
    public function onModelDeleted(Model $model): void;
}