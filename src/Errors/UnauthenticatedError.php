<?php

namespace GraphQL\Errors;

class UnauthenticatedError extends GraphQLError
{
    protected $code = "UNAUTHENTICATED";
}

?>