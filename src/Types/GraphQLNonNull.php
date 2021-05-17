<?php

namespace GraphQL\Types;

use GraphQL\Fields\GraphQLQueryField;

/**
 * Class GraphQLNonNull
 * @package GraphQL\Types
 */
class GraphQLNonNull extends GraphQLType
{
    protected $type = "NonNull";
    protected $description = "Default GraphQL Wrapper Type for Non-Nullability";

    private $innerType;

    /**
     * @return string
     */
    public function getName(): string
    {
        return parent::getName() . "({$this->getInnerType()->getName()})";
    }

    /**
     * GraphQLNonNull constructor.
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

