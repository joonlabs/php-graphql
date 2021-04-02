<?php

namespace GraphQL\Arguments;

use GraphQL\Types\GraphQLType;

class GraphQLQueryArgument extends GraphQLArgument{

    private $value;
    private $location;

    /**
     * GraphQLQueryArgument constructor.
     * @param string $id
     * @param $value
     * @param null $location
     */
    public function __construct(string $id, $value, $location=null)
    {
        $this->id = $id;
        $this->value = $value;
        $this->location = $location;
    }

    /**
     * Returns the value of the argument.
     *
     * @return mixed
     */
    public function __getValue()
    {
        return $this->value;
    }

    /**
     * Returns the location of the argument.
     *
     * @return null
     */
    public function __getLocation()
    {
        return $this->location;
    }
}