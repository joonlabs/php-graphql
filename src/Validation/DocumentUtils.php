<?php

namespace GraphQL\Validation;


/**
 * Class DocumentUtils
 * @package GraphQL\Validation
 */
class DocumentUtils
{
    private static $cacheNodesOfKey = [];
    private static $cacheNodesOfKind = [];

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
    public static function getAllNodesOfKind(array $document, string $kind): array
    {
        $hashKey = self::getHashKey($document, $kind);
        if ((self::$cacheNodesOfKind[$hashKey] ?? null) !== null)
            return self::$cacheNodesOfKind[$hashKey];

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

        self::$cacheNodesOfKind[$hashKey] = $nodes;

        return $nodes;
    }

    /**
     * Returns all Nodes in a Document that are identified by a given key.
     * @param array $document
     * @param string $kind
     * @return array
     */
    public static function getAllNodesOfKey(array $document, string $key): array
    {
        $hashKey = self::getHashKey($document, $key);
        if ((self::$cacheNodesOfKey[$hashKey] ?? null) !== null)
            return self::$cacheNodesOfKey[$hashKey];

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

        self::$cacheNodesOfKey[$hashKey] = $nodes;

        return $nodes;
    }

    /**
     * Builds an unique hash-key based on a document and an identifier.
     *
     * @param array $document
     * @param string $identifier
     * @return string
     */
    private static function getHashKey(array $document, string $identifier): string
    {
        return crc32(
                serialize(
                    $document
                )
            ) . $identifier;
    }
}

