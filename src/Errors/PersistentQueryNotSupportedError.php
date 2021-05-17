<?php

namespace GraphQL\Errors;

/**
 * Class PersistentQueryNotSupportedError
 * @package GraphQL\Errors
 */
class PersistentQueryNotSupportedError extends GraphQLError
{
    protected $code = "PERSISTED_QUERY_NOT_SUPPORTED";
}

