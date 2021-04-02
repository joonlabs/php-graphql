<?php

namespace GraphQL\Types;

use GraphQL\Errors\ValidationError;
use GraphQL\Fields\GraphQLQueryField;
use GraphQL\Resolvers\QueryResolver;
use GraphQL\Variables\GraphQLVariableHolder;

class GraphQLList extends GraphQLType
{
    protected $type = "GraphQLList";
    protected $description = "Default GraphQL Wrapper Type for Lists";

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