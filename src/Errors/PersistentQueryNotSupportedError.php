<?php

namespace GraphQL\Errors;

class PersistentQueryNotSupportedError extends GraphQLError
{
    protected $code = "PERSISTED_QUERY_NOT_SUPPORTED";
}

