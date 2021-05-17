<?php

namespace GraphQL\Types;

use GraphQL\Fields\GraphQLQueryField;
use GraphQL\Resolvers\QueryResolver;
use GraphQL\Variables\GraphQLVariableHolder;

/**
 * Class GraphQLList
 * @package GraphQL\Types
 */
class GraphQLList extends GraphQLType
{
    protected $type = "List";
    protected $description = "Default GraphQL Wrapper Type for Lists";

    private $innerType;

    /**
     * @return string
     */
    public function getName(): string
    {
        return parent::getName() . "({$this->getInnerType()->getName()})";
    }

    /**
     * GraphQLList constructor.
     * @param GraphQLType $innerType
     */
    public function __construct(GraphQLType $innerType)
    {
        $this->innerType = $innerType;
    }

    /**
     * @return GraphQLType
     */
    public function getInnerType(): GraphQLType
    {
        return $this->innerType;
    }
}

