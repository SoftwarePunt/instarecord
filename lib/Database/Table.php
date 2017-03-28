<?php

namespace Instasell\Instarecord\Database;

use Instasell\Instarecord\Utils\TextTransforms;

/**
 * Represents a database table, which backs a model.
 */
class Table
{
    /**
     * Based on a Model name, returns the table name.
     *
     * @param string $modelClassName
     * @return string
     */
    public static function getTableNameForClass(string $modelClassName): string
    {
        $tableName = $modelClassName;

        // First, extract the namespace from the model name
        if (preg_match('@\\\\([\w]+)$@', $tableName, $matches)) {
            $tableName = $matches[1];
        }

        // Second, pluralize the name
        $tableName = TextTransforms::pluralize($tableName);

        // Finally, normalize it to database conventions using the "column normalizer"
        $tableName = Column::getColumNameForProperty($tableName);
        return $tableName;
    }
}