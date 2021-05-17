<?php

namespace GraphQL\Errors;

/**
 * Class ValidationError
 * @package GraphQL\Errors
 */
class ValidationError extends GraphQLError
{
    protected $code = "GRAPHQL_VALIDATION_FAILED";
}

