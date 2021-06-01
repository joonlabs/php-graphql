<?php

namespace GraphQL\Utilities;

use GraphQL\Errors\GraphQLError;

/**
 * Class LocatedError
 * @package GraphQL\Utilities
 */
abstract class LocatedError
{
    /**
     * @param GraphQLError $originalError
     * @param $fieldNodes
     * @param $path
     * @return GraphQLError
     */
    public static function from(GraphQLError $originalError, $fieldNodes, $path): GraphQLError
    {
        $errorClassName = get_class($originalError);
        return new $errorClassName(
            $originalError->getMessage(),
            $fieldNodes[0],
            $path,
            $originalError->getCustomExtensions()
        );
    }

}

