<?php
namespace GraphQL\Utilities;

use GraphQL\Errors\GraphQLError;
use GraphQL\Schemas\Schema;

abstract class LocatedError{
    public static function from(GraphQLError $originalError, $fieldNodes, $path)
    {
        return new GraphQLError(
            $originalError->getMessage(),
            $fieldNodes[0],
            $path
        );
    }

}
?>