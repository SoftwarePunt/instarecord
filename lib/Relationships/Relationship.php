<?php

namespace SoftwarePunt\Instarecord\Relationships;

use Attribute;
use SoftwarePunt\Instarecord\Model;

/**
 * Defines a relationship (one-to-X) between two models.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Relationship
{
    public readonly string $modelClass;
    public readonly ?string $columnName;

    /**
     * @param string $modelClass The class this relationship points to.
     * @param string|null $columnName The local or foreign column name, depending on the type of relationship.
     *  If null, this will be set to the property name + "_id" suffix.
     */
    public function __construct(string $modelClass, ?string $columnName = null)
    {
        $this->modelClass = $modelClass;
        $this->columnName = $columnName;
    }

    public function tryLoadModel(): Model
    {
        if (!class_exists($this->modelClass)) {
            throw new \LogicException("Model class '{$this->modelClass}' does not exist.");
        }

        $instance = new $this->modelClass();

        if (!($instance instanceof Model)) {
            throw new \LogicException("Model class '{$this->modelClass}' does not extend SoftwarePunt\Instarecord\Model.");
        }

        return $instance;
    }
}