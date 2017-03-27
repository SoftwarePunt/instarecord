<?php

namespace Instasell\Instarecord;

/**
 * The base class for all Instasell models.
 */
class Model
{
    /**
     * Gets a list of the names of all this model's properties.
     * 
     * Properties are public variables defined in the class that can be get or set.
     * Each property refers to a real database column and may reflect a relationship.
     * 
     * @return array
     */
    public function getPropertyNames(): array
    {
        $rfClass = new \ReflectionClass($this);
        $rfProperties = $rfClass->getProperties(\ReflectionProperty::IS_PUBLIC);

        $properties = [];
        
        foreach ($rfProperties as $rfProperty) {
            $properties[] = $rfProperty->getName();
        }
        
        return $properties;
    }

    /**
     * Gets a list of the column names defined in this model.
     * This is translated based on the properties defined in this model.
     * 
     * @return array
     */
    public function getColumnNames(): array
    {
        $propertyNames = $this->getPropertyNames();
        $columnNames = [];
        
        foreach ($propertyNames as $propertyName) {
            $columnNames[] = Table::translateColumnName($propertyName);
        }
        
        return $columnNames;
    }
}