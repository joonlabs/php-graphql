<?php

namespace GraphQL\Variables;

class GraphQLVariableReference{
    private $id;
    private $location;

    /**
     * Creates a GraphQLVariableReference object to reference a GraphQLVariableHolder object.
     *
     * GraphQLVariableHolder constructor.
     * @param array $variables
     */
    public function __construct(string $id, array $location=null)
    {
        $this->id = $id;
        $this->location = $location;
    }

    /**
     * Returns the id of the variable reference.
     *
     * @return string
     */
    public function __getId() : string
    {
        return $this->id;
    }

    /**
     * Returns the Location of the reference.
     *
     * @return array|null
     */
    public function __getLocation()
    {
        return $this->location;
    }
}