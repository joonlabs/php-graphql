<?php
namespace GraphQL\Utilities;

use GraphQL\Errors\GraphQLError;

abstract class Errors
{

    /**
     * Returns a pretty printed array version of an array of errors
     * @param array $errors
     * @return array|array[]
     */
    public static function prettyPrintErrors(array $errors)
    {
        return array_map(function (GraphQLError $error) {
            return [
                "message" => $error->getMessage(),
                "locations" => $error->getLocations(),
                "path" => $error->getPath(),
                "extensions" => [
                    "code" => $error->getErrorCode()
                ]
            ];
        }, $errors);
    }
}

?>
