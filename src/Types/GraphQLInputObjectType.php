<?php

namespace GraphQL\Types;

use Closure;
use GraphQL\Fields\GraphQLQueryField;

/**
 * Class GraphQLInputObjectType
 * @package GraphQL\Types
 */
class GraphQLInputObjectType extends GraphQLObjectType
{
    protected $type;
    protected $description;

    /**
     * GraphQLInputObjectType constructor.
     * @param string $type
     * @param string $description
     * @param Closure $fields
     */
    public function __construct(
        string $type,
        string $description,
        Closure $fields
    )
    {
        parent::__construct($type, $description, $fields, []);
    }
}

