<?php

namespace Instasell\Instarecord\Database;

use Instasell\Instarecord\Model;

/**
 * A model-specific query.
 */
class ModelQuery extends Query
{
    /**
     * The fully qualified class name of the model.
     *
     * @var string
     */
    protected $modelName;

    /**
     * A blank reference for the model we are managing.
     *
     * @var Model
     */
    protected $referenceModel;

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

        if (!$this->referenceModel instanceof Model) {
            throw new DatabaseException("ModelQuery: Invalid model class, does not extend from Model: {$modelName}");
        }

        // Preset the table name for this query
        $this->from($this->referenceModel->getTableName());
    }

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

    /**
     * Queries all rows and returns them as an array of model instances.
     *
     * @return array
     */
    public function queryAllModels(): array
    {
        $rows = $this->queryAllRows();
        $models = [];

        foreach ($rows as $row) {
            $models[] = new $this->modelName($row);
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
        $rows = $this->queryAllRows();
        $models = [];

        foreach ($rows as $row) {
            /**
             * @var Model $instance
             */
            $instance = new $this->modelName($row);

            $models[$indexKey ? $instance->$indexKey :
                $instance->getPrimaryKeyValue()] = $instance;
        }

        return $models;
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
        
        return new $this->modelName($row);
    }
}
