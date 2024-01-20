<?php

namespace SoftwarePunt\Instarecord\Relationships;

use SoftwarePunt\Instarecord\Database\ModelQuery;
use SoftwarePunt\Instarecord\Instarecord;
use SoftwarePunt\Instarecord\Model;

/**
 * Utility for managing a one-to-many relationship between two models.
 * Caches loaded models and supports lazy-loading.
 */
class HasManyRelationship
{
    public readonly Model $hostModel;
    public readonly string $targetModelClass;
    public readonly string $foreignKeyColumn;

    private Model $referenceModel;
    private array $loadedModels;
    private bool $isFullyLoaded;

    public function __construct(Model $hostModel, string $targetModelClass, string $foreignKeyColumn)
    {
        $this->hostModel = $hostModel;
        $this->targetModelClass = $targetModelClass;
        $this->foreignKeyColumn = $foreignKeyColumn;

        $this->referenceModel = new $targetModelClass();
        $this->loadedModels = [];
        $this->isFullyLoaded = false;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Query

    public function getPrimaryKeyValue(): mixed
    {
        return $this->hostModel->getPrimaryKeyValue();
    }

    // -----------------------------------------------------------------------------------------------------------------
    // API - Primary

    /**
     * Begins a query for this relationship, with a predefined WHERE clause for the foreign key.
     *
     * @param bool $hookResults If true, the query results will be hooked into the relationship cache.
     * @return ModelQuery
     */
    public function query(bool $hookResults = true): ModelQuery
    {
        $pkVal = $this->getPrimaryKeyValue();

        if (empty($pkVal))
            throw new \InvalidArgumentException("Cannot query relationship without having primary key set");

        $query = $this->referenceModel::query()
            ->where("{$this->foreignKeyColumn} = ?", $pkVal);

        $query->addResultHook(function (Model $model) {
            $pkVal = $model->getPrimaryKeyValue();
            $this->loadedModels[$pkVal] = $model;
        });

        return $query;
    }

    /**
     * Gets all models in this relationship.
     * If not previously loaded, causes all or the remaining models to be loaded from the database.
     *
     * @return Model[]
     */
    public function all(): array
    {
        if ($this->isFullyLoaded) {
            // Full model load - return cached
            return $this->loadedModels;
        }

        $loadQuery = $this->query();

        if (!empty($this->loadedModels)) {
            // Partial model list loaded - check what we need to load
            $loadedIds = array_keys($this->loadedModels);
            $missingModels = $loadQuery->andWhere("id NOT IN (?)", $loadedIds)
                ->queryAllModelsIndexed();
            foreach ($missingModels as $missingModel) {
                $this->loadedModels[$missingModel->getPrimaryKeyValue()] = $missingModel;
            }
        } else {
            // No models loaded - load all
            $this->loadedModels = $loadQuery->queryAllModelsIndexed();
        }

        $this->isFullyLoaded = true;
        return $this->loadedModels;
    }

    /**
     * Gets a single model in this relationship by its primary key.
     * Retrieves from cache if already loaded.
     *
     * @param mixed $fkValue
     * @return Model|null
     */
    public function fetch(mixed $fkValue): ?Model
    {
        if (isset($this->loadedModels[$fkValue])) {
            return $this->loadedModels[$fkValue];
        }

        return $this->query()
            ->andWhere("{$this->foreignKeyColumn} = ?", $fkValue)
            ->querySingleModel();
    }

    // -----------------------------------------------------------------------------------------------------------------
    // API - Utils

    /**
     * Reset/invalidate the loaded model cache.
     */
    public function reset(): void
    {
        $this->loadedModels = [];
        $this->isFullyLoaded = false;
    }

    /**
     * Cache a model for this relationship.
     */
    public function addLoaded(Model $model): void
    {
        if ($model::class !== $this->targetModelClass) {
            throw new \InvalidArgumentException("Cannot add different model to relationship of type {$this->targetModelClass}");
        }

        $pkVal = $model->getPrimaryKeyValue();

        if (empty($pkVal)) {
            throw new \InvalidArgumentException("Cannot add model with empty primary key to relationship of type {$this->targetModelClass}");
        }

        $this->loadedModels[$pkVal] = $model;
    }

    /**
     * Cache an array of models for this relationship.
     */
    public function addLoadedArray(array $models): void
    {
        foreach ($models as $model) {
            $this->addLoaded($model);
        }
    }
}