<?php

namespace SoftwarePunt\Instarecord\Relationships;

use SoftwarePunt\Instarecord\Instarecord;
use SoftwarePunt\Instarecord\Model;

class RelationshipBatcher
{
    public readonly Model $referenceModel;
    public readonly string $modelName;
    public readonly array $rows;
    private readonly array $relationshipColumns;
    public readonly bool $hasRelationships;

    /**
     * @var RelationshipBatch[]
     */
    private array $batches = [];

    public function __construct(Model $referenceModel, array $rows)
    {
        $this->referenceModel = $referenceModel;
        $this->modelName = $referenceModel::class;
        $this->rows = $rows;

        // Determine relationships
        $this->relationshipColumns = $this->referenceModel->getTableInfo()->getRelationshipColumns();
        $this->hasRelationships = !empty($this->relationshipColumns);

        // Create batches
        $this->batches = [];
        if ($this->hasRelationships) {
            foreach ($this->relationshipColumns as $relationshipColumn) {
                $this->batches[] = new RelationshipBatch($relationshipColumn);
            }
        }
    }

    /**
     * @return Model[]
     */
    public function loadAllModels(): array
    {
        $models = [];
        $cache = Instarecord::modelCache();

        foreach ($this->rows as $row) {
            $model = new $this->modelName($row, loadRelationships: false);

            if ($cache) {
                $cache->onModelFetched($model);
            }

            foreach ($this->batches as $batch) {
                $batch->checkModel($model, $row);
            }

            $models[] = $model;
        }

        foreach ($this->batches as $batch) {
            $batch->queryAndApply($models);
        }

        return $models;
    }
}