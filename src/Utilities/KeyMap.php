<?php

namespace GraphQL\Utilities;

use Closure;

abstract class KeyMap
{
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

