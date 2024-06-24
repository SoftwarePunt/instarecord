<?php

namespace SoftwarePunt\Instarecord\Validation;

use SoftwarePunt\Instarecord\Model;

class ModelValidator
{
    private readonly Model $instance;

    public function __construct(Model $instance)
    {
        $this->instance = $instance;
    }

    public function validate(): ValidationResults
    {
        $results = [];

        $table = $this->instance->getTableInfo();

        foreach ($this->instance->getPropertyValues() as $propName => $propVal) {
            $column = $table->getColumnByPropertyName($propName);
            if ($column === null)
                // No column associated with property, not part of the model
                continue;

            $validators = $column->getValidators();
            if (empty($validators))
                // No validators for this column, nothing to do
                continue;

            $friendlyName = $column->getFriendlyName();

            foreach ($validators as $validator) {
                $result = $validator->validate($friendlyName, $propVal);
                $results[] = $result;

                if (!$result->ok) {
                    break; // Stop at the first failure, max one message per property
                }
            }
        }

        return new ValidationResults($results);
    }
}