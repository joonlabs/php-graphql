<?php

namespace GraphQL\Errors;

class ValidationError extends GraphQLError
{
    protected $code = "GRAPHQL_VALIDATION_FAILED";
}

?>