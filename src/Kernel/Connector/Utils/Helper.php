<?php

namespace App\Kernel\Connector\Utils;

class Helper
{
    /**
     * Return Property name transform to SQL column
     *  $firstName → first_name
     * 
     * @param string $property
     * @return string
     */
    public static function propertyToColumn(string $property): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $property));
    }

    /**
     * Return SQL column transform to Property name
     * user_name → $userName
     * 
     * @param string $column
     * @return string
     */
    public static function columnToProperty(string $column): string
    {
        return lcfirst(str_replace('_', '', ucwords($column, '_')));
    }
}