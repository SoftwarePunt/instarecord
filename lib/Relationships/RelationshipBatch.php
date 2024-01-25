<?php

namespace SoftwarePunt\Instarecord\Relationships;

use SoftwarePunt\Instarecord\Database\Column;
use SoftwarePunt\Instarecord\Model;

class RelationshipBatch
{
    public readonly string $columnName;
    public readonly string $propertyName;
    public readonly string $relationshipClass;

    private array $modelPkToFk = [];
    private array $distinctFkValues = [];

    public function __construct(Column $column)
    {
        $this->columnName = $column->getColumnName();
        $this->propertyName = $column->getPropertyName();
        $this->relationshipClass = $column->getRelationshipClass();

        $this->modelPkToFk = [];
        $this->distinctFkValues = [];
    }

    /**
     * Checks a model after it has been loaded from the database, and prepares the batch query.
     *
     * @param Model $model
     * @param array $backingRow
     * @return void
     */
    public function checkModel(Model $model, array $backingRow): void
    {
        $backingFk = $backingRow[$this->columnName];

        if ($backingFk === null) {
            // Database value is null, so set property to null - no further action required
            $model->{$this->propertyName} = null;
            return;
        }

        $modelPk = $model->getPrimaryKeyValue();
        $this->modelPkToFk[$modelPk] = $backingFk;
        if (!in_array($backingFk, $this->distinctFkValues))
            $this->distinctFkValues[] = $backingFk;
    }

    /**
     * Executes the combined/batch query, applying the results to the given models.
     *
     * @param array $models
     * @return void
     */
    public function queryAndApply(array $models): void
    {
        if (empty($this->distinctFkValues))
            // Nothing to do / all nulls
            return;

        $referenceModel = new $this->relationshipClass();
        if (!$referenceModel instanceof Model)
            throw new \Exception("Relationship class {$this->relationshipClass} is not a Model.");

        $referencePkColumn = $referenceModel->getPrimaryKeyColumnName();

        $results = $referenceModel::query()
            ->where("`{$referencePkColumn}` IN (?)", $this->distinctFkValues)
            ->queryAllModelsIndexed();

        $propName = $this->propertyName;

        foreach ($models as $model) {
            /**
             * @var $model Model
             */
            $modelPk = $model->getPrimaryKeyValue();
            $backingFk = $this->modelPkToFk[$modelPk];
            $fkResult = $results[$backingFk] ?? null;

            $model->$propName = $fkResult;
        }
    }
}