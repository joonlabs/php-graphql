<?php

namespace GraphQL\Utilities;

use GraphQL\Errors\GraphQLError;
use GraphQL\Schemas\Schema;

abstract class OperationRootType
{
    public static function getOperationRootType(Schema $schema, $operation)
    {
        if ($operation["operation"] === "query") {
            $queryType = $schema->getQueryType();
            if ($queryType === null) {
                throw new GraphQLError(
                    "Schema does not define the required query root type."
                );
            }
            return $queryType;
        }

        if ($operation["operation"] === "mutation") {
            $mutationType = $schema->getMutationType();
            if ($mutationType === null) {
                throw new GraphQLError(
                    "Schema is not configured for mutations."
                );
            }
            return $mutationType;
        }

        throw new GraphQLError(
            "Can only have query and mutation operations."
        );
    }

}

?>