<?php

namespace GraphQL\Errors;

/**
 * Class UnauthenticatedError
 * @package GraphQL\Errors
 */
class UnauthenticatedError extends GraphQLError
{
    protected $code = "UNAUTHENTICATED";
}

