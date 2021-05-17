<?php

namespace GraphQL\Errors;

class UnexpectedTokenError extends ParseFailedError
{
    protected $code = "UNEXPECTED_TOKEN";
}

