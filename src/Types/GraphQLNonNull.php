<?php

namespace GraphQL\Types;

use GraphQL\Fields\GraphQLQueryField;

class GraphQLNonNull extends GraphQLType
{
    protected $type = "GraphQLNonNull";
    protected $description = "Default GraphQL Wrapper Type for Non-Nullability";

    private $innerType;

    public function getName(): string
    {
        return parent::getName()."({$this->getInnerType()->getName()})";
    }

    public function __construct(GraphQLType $innerType)
    {
        $this->innerType = $innerType;
    }

    public function getInnerType(){
        return $this->innerType;
    }
}

?>