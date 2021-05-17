<?php

namespace GraphQL\Errors;

/**
 * Class ParseFailedError
 * @package GraphQL\Errors
 */
class ParseFailedError extends GraphQLError
{
    protected $code = "GRAPHQL_PARSE_FAILED";
}

