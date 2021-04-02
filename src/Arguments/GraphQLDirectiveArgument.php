<?php

namespace GraphQL\Arguments;

use GraphQL\Types\GraphQLType;

class GraphQLDirectiveArgument extends GraphQLArgument{
    private $defaultValue;

    /**
     * GraphQLDirectiveArgument constructor.
     * @param string $id
     * @param GraphQLType $type
     * @param null $defaultValue
     */
    public function __construct(string $id, GraphQLType $type, $defaultValue=null)
    {
        $this->id = $id;
        $this->type = $type;
        $this->defaultValue = $defaultValue;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->id;
    }

    /**
     * @return GraphQLType
     */
    public function getType(): GraphQLType
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }
}