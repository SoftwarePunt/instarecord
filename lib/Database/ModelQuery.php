<?php

namespace SoftwarePunt\Instarecord\Database;

use SoftwarePunt\Instarecord\Model;
use SoftwarePunt\Instarecord\Models\IReadOnlyModel;
use SoftwarePunt\Instarecord\Models\ModelAccessException;
use SoftwarePunt\Instarecord\Relationships\RelationshipBatcher;

/**
 * A model-specific query.
 */
class ModelQuery extends Query
{
    /**
     * The fully qualified class name of the model.
     */
    protected string $modelName;

    /**
     * A blank reference for the model we are managing.
     */
    protected Model $referenceModel;

    /**
     * @var callable[] An array of callbacks to invoke when a model is loaded.
     */
    protected array $resultHooks = [];

    /**
     * Constructs a new model query.
     *
     * @param Connection $connection The connection this query is being ran on.
     * @param string $modelName The fully qualified class name of the model.
     */
    public function __construct(Connection $connection, string $modelName)
    {
        parent::__construct($connection);

        if (!class_exists($modelName)) {
            throw new DatabaseException("ModelQuery: Invalid model name, not a loadable class: {$modelName}");
        }

        $this->modelName = $modelName;
        $this->referenceModel = new $modelName;
        $this->resultHooks = [];

        if (!$this->referenceModel instanceof Model) {
            throw new DatabaseException("ModelQuery: Invalid model class, does not extend from Model: {$modelName}");
        }

        // Preset the table name for this query
        $this->from($this->referenceModel->getTableName());
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Query - Wheres

    /**
     * Adds a WHERE constraint to match the primary key in the given model instance.
     *
     * @param Model $instance
     * @return ModelQuery|$this
     */
    public function wherePrimaryKeyMatches(Model $instance): ModelQuery
    {
        $columnName = $this->referenceModel->getPrimaryKeyPropertyName();
        $this->where("{$columnName} = ?", $instance->$columnName);
        return $this;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Query - Executes

    /**
     * Queries all rows and returns them as an array of model instances.
     *
     * @return array
     */
    public function queryAllModels(): array
    {
        $rows = $this->queryAllRows();

        // Wrap in batch loader for relationships (if any) to optimize queries
        $relationshipBatcher = new RelationshipBatcher($this->referenceModel, $rows);
        $models = $relationshipBatcher->loadAllModels();

        // Fire result hooks
        if (!empty($this->resultHooks)) {
            foreach ($models as $model) {
                $this->fireResultHook($model);
            }
        }

        return $models;
    }

    /**
     * Same as queryAllModels(), but indexes all options by their PK value.
     *
     * @param string $indexKey The key to index the array with. If left at NULL, the primary key will be used as index.
     * @return array
     */
    public function queryAllModelsIndexed(?string $indexKey = null): array
    {
        $models = $this->queryAllModels();
        $indexed = [];

        foreach ($models as $model) {
            $indexed[$indexKey ? $model->$indexKey : $model->getPrimaryKeyValue()] = $model;
        }

        return $indexed;
    }

    /**
     * Queries a single row and returns it as a model instance.
     *
     * @return Model|null Model instance, or NULL if there was no result.
     */
    public function querySingleModel(): ?Model
    {
        $row = $this->querySingleRow();
        
        if ($row == null) {
            return null;
        }
        
        $model = new $this->modelName($row);
        $this->fireResultHook($model);
        return $model;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Util

    /**
     * @throws ModelAccessException
     */
    protected function verifyAccess(): void
    {
        if ($this->referenceModel instanceof IReadOnlyModel) {
            $restrictedStatementTypes = [
                self::QUERY_TYPE_DELETE,
                self::QUERY_TYPE_INSERT,
                self::QUERY_TYPE_UPDATE
            ];

            if (in_array($this->statementType, $restrictedStatementTypes)) {
                throw new ModelAccessException(
                    "Cannot perform INSERT, DELETE or UPDATE queries for read only model: {$this->modelName}"
                );
            }
        }
    }

    /**
     * @inheritDoc
     * @throws ModelAccessException
     */
    public function createStatementText(): string
    {
        $this->verifyAccess();
        return parent::createStatementText();
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Result hooks

    public function addResultHook(callable $callback): void
    {
        $this->resultHooks[] = $callback;
    }

    public function fireResultHook(Model $model): void
    {
        foreach ($this->resultHooks as $hook) {
            $hook($model);
        }
    }
}
