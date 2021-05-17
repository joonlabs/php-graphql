<?php

namespace GraphQL\Errors;

/**
 * Class PersistentQueryNotFound
 * @package GraphQL\Errors
 */
class PersistentQueryNotFound extends GraphQLError
{
    protected $code = "PERSISTED_QUERY_NOT_FOUND";
}

