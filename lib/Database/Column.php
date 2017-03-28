<?php

namespace Instasell\Instarecord\Database;

/**
 * Represents a Column within a Table.
 */
class Column
{
    /**
     * Given a column name, normalizes it to its property name.
     * The normalization process takes a MySQL compliant "column_name" and converts it to a more PHP-like $columnName.
     *
     * @param string $columnName The column name from the database, to be converted to its property equivalent.
     * @return string
     */
    public static function getPropertyNameForColumn(string $columnName)
    {
        $columnName = preg_replace('/[_-]+/', '_', trim($columnName));
        $columnName = str_replace(' ', '_', $columnName);

        $camelized = '';

        for ($i = 0, $n = strlen($columnName); $i < $n; ++$i) {
            if ($columnName[$i] == '_' && $i + 1 < $n) {
                $camelized .= strtoupper($columnName[++$i]);
            } else {
                $camelized .= $columnName[$i];
            }
        }

        $camelized = trim($camelized, ' _');

        if (strlen($camelized) > 0) {
            $camelized[0] = strtolower($camelized[0]);
        }

        return $camelized;
    }

    /**
     * Given a property name, normalizes it to its column name.
     * The normalization process takes a PHP-like $columnName and converts it to a MySQL compliant "column_name".
     *
     * @param string $propertyName The property name from the code, to be converted to its column equivalent.
     * @return string
     */
    public static function getColumNameForProperty(string $propertyName)
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $propertyName, $matches);

        $ret = $matches[0];

        foreach ($ret as &$match) {
            $match = ($match == strtoupper($match) ? strtolower($match) : lcfirst($match));
        }

        return implode('_', $ret);
    }
}