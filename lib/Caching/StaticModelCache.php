<?php

namespace SoftwarePunt\Instarecord\Caching;

use Override;
use ReflectionClass;
use SoftwarePunt\Instarecord\Model;

/**
 * Simple static model cache. Caches model instances
 */
class StaticModelCache implements IModelCache
{
    /**
     * @var Model[][]
     */
    private array $pools = [];

    /**
     * @var ModelCacheMode[]
     */
    private array $cacheModes = [];

    private function getCacheMode(string $modelClass): ModelCacheMode
    {
        if (isset($this->cacheModes[$modelClass])) {
            return $this->cacheModes[$modelClass];
        }

        $rfClass = new ReflectionClass($modelClass);
        $rfAttribute = $rfClass->getAttributes(CacheableModel::class)[0] ?? null;

        $cacheMode = ModelCacheMode::NONE;

        if ($rfAttribute) {
            if ($attribute = $rfAttribute->newInstance()) {
                /**
                 * @var $attribute CacheableModel
                 */
                $cacheMode = $attribute->mode;
            }
        }

        if ($cacheMode !== ModelCacheMode::NONE) {
            $this->pools[$modelClass] ??= [];
        }

        $this->cacheModes[$modelClass] = $cacheMode;
        return $cacheMode;
    }

    #[Override]
    public function beforeModelFetch(Model $model, int|string $pkVal): ?Model
    {
        $modelClass = $model::class;
        $cacheMode = self::getCacheMode($modelClass);

        if ($cacheMode === ModelCacheMode::NONE) {
            return null;
        }

        $pkVal = strval($pkVal);
        return $this->pools[$modelClass][$pkVal] ?? null;
    }

    #[Override]
    public function onModelSaved(Model $model): void
    {
        $modelClass = $model::class;
        $cacheMode = self::getCacheMode($modelClass);

        if ($cacheMode === ModelCacheMode::NONE) {
            return;
        }

        $pkVal = strval($model->getPrimaryKeyValue());
        $this->pools[$modelClass][$pkVal] = $model;
    }

    #[Override]
    public function onModelFetched(Model $model): void
    {
        $modelClass = $model::class;
        $cacheMode = self::getCacheMode($modelClass);

        if ($cacheMode === ModelCacheMode::NONE) {
            return;
        }

        $pkVal = strval($model->getPrimaryKeyValue());
        $this->pools[$modelClass][$pkVal] = $model;
    }

    #[Override]
    public function onModelDeleted(Model $model): void
    {
        $modelClass = $model::class;
        $cacheMode = self::getCacheMode($modelClass);

        if ($cacheMode === ModelCacheMode::NONE) {
            return;
        }

        if ($pkVal = strval($model->getPrimaryKeyValue())) {
            unset($this->pools[$modelClass][$pkVal]);
        }
    }
}