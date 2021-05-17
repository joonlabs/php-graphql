<?php

namespace GraphQL\Types;

use Closure;
use GraphQL\Fields\GraphQLQueryField;

class GraphQLInputObjectType extends GraphQLObjectType
{
    protected $type;
    protected $description;

    public function __construct(
        string $type,
        string $description,
        Closure $fields
    )
    {
        parent::__construct($type, $description, $fields, []);
    }
}

