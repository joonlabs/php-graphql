<?php
namespace GraphQL\Utilities;

abstract class KeyMap{
    public static function map(?array $items, \Closure $function)
    {
        $items = $items ?? []; // repair null values
        $result = [];
        foreach ($items as $item) {
            $result[$function($item)] = $item;
        }
        return $result;
    }
}
?>