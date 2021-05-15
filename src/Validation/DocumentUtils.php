<?php

namespace GraphQL\Validation;


class DocumentUtils
{
    /**
     * Returns all FragmentDefinitions
     * @param array $document
     * @return array
     */
    public static function getFragmentDefinitions(array $document): array
    {
        return array_filter($document["definitions"], function ($definition) {
            return $definition["kind"] === "FragmentDefinition";
        });
    }

    /**
     * Returns all Nodes in a Document that are from a given kind.
     * @param array $document
     * @param string $kind
     * @return array
     */
    public static function getAllNodesOfKind(array $document, string $kind)
    {
        $keys = array_keys($document);

        $nodes = [];

        foreach ($keys as $k) {
            if (is_array($document[$k]) && array_key_exists("kind", $document[$k]) && $document[$k]["kind"] === $kind) {
                $nodes[] = $document[$k];
            }
            if (is_array($document[$k])) {
                $nodes = array_merge($nodes, self::getAllNodesOfKind($document[$k], $kind));
            }
        }

        return $nodes;
    }

    /**
     * Returns all Nodes in a Document that are identified by a given key.
     * @param array $document
     * @param string $kind
     * @return array
     */
    public static function getAllNodesOfKey(array $document, string $key)
    {
        $keys = array_keys($document);

        $nodes = [];

        foreach ($keys as $k) {
            if ($k === $key) {
                $nodes[] = $document[$k];
            }
            if (is_array($document[$k])) {
                $nodes = array_merge($nodes, self::getAllNodesOfKey($document[$k], $key));
            }
        }

        return $nodes;
    }
}

?>