<?php

namespace GraphQL\Errors;

class UnexpectedEndOfInputError extends ParseFailedError
{
    protected $code = "UNEXPECTED_END_OF_INPUT";
}

