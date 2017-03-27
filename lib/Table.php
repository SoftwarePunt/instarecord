<?php

namespace Instasell\Instarecord;

/**
 * Represents a database table, which backs a model.
 */
class Table
{
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