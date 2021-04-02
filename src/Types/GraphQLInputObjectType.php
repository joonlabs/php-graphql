<?php

namespace GraphQL\Types;

use GraphQL\Errors\BadUserInputError;
use GraphQL\Fields\GraphQLQueryField;
use GraphQL\Fields\GraphQLTypeField;

class GraphQLInputObjectType extends GraphQLObjectType
{
    protected $type;
    protected $description;

    public function __construct(
        string $type,
        string $description,
        \Closure $fields
    )
    {
        parent::__construct($type, $description, $fields, []);
    }
}

?>