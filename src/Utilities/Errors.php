<?php
namespace GraphQL\Utilities;

use GraphQL\Errors\GraphQLError;

/**
 * Class Errors
 * @package GraphQL\Utilities
 */
abstract class Errors
{

    /**
     * Returns a pretty printed array version of an array of errors
     * @param array $errors
     * @return array|array[]
     */
    public static function prettyPrintErrors(array $errors): array
    {
        return array_map(function (GraphQLError $error) {
            return [
                "message" => $error->getMessage(),
                "locations" => $error->getLocations(),
                "extensions" => $error->getExtensions(),
                "path" => $error->getSimplifiedPath()
            ];
        }, $errors);
    }
}


