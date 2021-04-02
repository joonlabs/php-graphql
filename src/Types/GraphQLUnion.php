<?php

namespace GraphQL\Types;

class GraphQLUnion extends GraphQLAbstractType
{
    protected $type = "GraphQLUnion";
    protected $description = "Default GraphQL Union Type";

    private $types;

    public function __construct(array $types)
    {
        $this->types = $types;
    }

    /**
     * @return array
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    public function getResolveType()
    {
        return null;
    }
}

?>