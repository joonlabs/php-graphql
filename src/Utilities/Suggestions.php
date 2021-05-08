<?php

namespace GraphQL\Utilities;

use GraphQL\Schemas\Schema;

abstract class Suggestions
{

    /**
     * Returns similar types to suggest sultions for typo errors.
     * @param string $input
     * @param array $possibilities
     * @param float|int $threshold
     * @return array
     */
    public static function suggest(string $input, array $possibilities, float $threshold = (2 / 3)): array
    {
        $suggestions = [];

        $calcedPossibilities = (array_map(function ($item) use ($input) {
            $perc = 0;
            similar_text($item, $input, $perc);
            return $perc / 100;
        }, $possibilities));

        foreach ($calcedPossibilities as $i => $possibility) {
            if ($possibility >= $threshold) {
                $suggestions[] = $possibilities[$i];
            }
        }

        return $suggestions;
    }

    public static function didYouMean(array $suggestions, string $prefix = " "): string
    {
        $text = "";
        if (count($suggestions) > 0) {
            $text = $prefix;
            $text .= "Did you mean \"" . implode("\" or \"", $suggestions) . "\"?";
        }
        return $text;
    }
}

?>