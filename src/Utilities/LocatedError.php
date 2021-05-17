<?php

namespace GraphQL\Utilities;

use GraphQL\Errors\GraphQLError;

abstract class LocatedError
{
    public static function from(GraphQLError $originalError, $fieldNodes, $path): GraphQLError
    {
        return new GraphQLError(
            $originalError->getMessage(),
            $fieldNodes[0],
            $path
        );
    }

}

