<?php

namespace GraphQL\Errors;

/**
 * Class ForbiddenError
 * @package GraphQL\Errors
 */
class ForbiddenError extends GraphQLError
{
    protected $code = "FORBIDDEN";
}

