<?php

namespace GraphQL\Arguments;

use GraphQL\Types\GraphQLType;

abstract class GraphQLArgument{
    protected $id;
    protected $type;

    /**
     * Returns the id of the argument.
     *
     * @return string
     */
    public function __getId() : string
    {
        return $this->id;
    }

    /**
     * Returns the type of the argument.
     *
     * @return GraphQLType
     */
    public function __getType() : GraphQLType
    {
        return $this->type;
    }
}