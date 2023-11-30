<?php

namespace App\Helpers;

class Helper
{
    /**
     * Create nested array from properties in array formate.
     *
     * @param array &$rootObj
     * @param array $properties
     * @param string $value
     * @return void
     */
    public static function createNestedArray(&$rootObj, $properties, $value)
    {
        $lastKey = array_pop($properties);
        $current = &$rootObj;

        foreach ($properties as $nestedKey) {
            $current = &$current[$nestedKey];
        }

        $current[$lastKey] = $value;

        // $firstProperty = array_shift($properties);
        // $current = &$rootObj;

        // if (is_array($current)) {
        //     if (!array_key_exists($firstProperty, $current)) {
        //         $current[$firstProperty] = [];
        //     }
        //     if (count($properties) > 0) {
        //         $this->createNestedArray($current[$firstProperty], $properties, $value);
        //     } else {
        //         $current[$firstProperty] = $value;
        //     }
        // }
    }
}
