<?php

namespace GraphQL\Utilities;

use Closure;

/**
 * Class KeyMap
 * @package GraphQL\Utilities
 */
abstract class KeyMap
{
    /**
     * @param array|null $items
     * @param Closure $function
     * @return array
     */
    public static function map(?array $items, Closure $function): array
    {
        $items = $items ?? []; // repair null values
        $result = [];
        foreach ($items as $item) {
            $result[$function($item)] = $item;
        }
        return $result;
    }
}

