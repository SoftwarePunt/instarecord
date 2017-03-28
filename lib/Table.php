<?php

namespace Instasell\Instarecord;

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
    public static function translateTableName(string $modelClassName): string 
    {
        $tableName = $modelClassName;
        
        // First, extract the namespace from the model name
        if (preg_match('@\\\\([\w]+)$@', $tableName, $matches)) {
            $tableName = $matches[1];
        }
        
        // Second, pluralize the name
        $tableName = TextTransforms::pluralize($tableName);
        
        // Finally, normalize it to database conventions using the "column normalizer"
        $tableName = self::translateColumnName($tableName);
        return $tableName;
    }
    
    /**
     * Given a property name, normalizes it to an underlying column name.
     *
     * The normalization process takes a PHP-compliant $camelCasedVariable and converts it to a more readable
     * "mysql_column_name".
     *
     * Example input and output:
     *  - "userId" becomes "user_id"
     *
     * @param string $propertyName The property name (i.e. variable name) being translated to a column name.
     * @return string
     */
    public static function translateColumnName(string $propertyName): string
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $propertyName, $matches);

        $ret = $matches[0];

        foreach ($ret as &$match) {
            $match = ($match == strtoupper($match) ? strtolower($match) : lcfirst($match));
        }

        return implode('_', $ret);
    }
}