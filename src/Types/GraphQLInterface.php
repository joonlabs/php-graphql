<?php

namespace GraphQL\Types;

class GraphQLInterface extends GraphQLAbstractType
{
    protected $type = "GraphQLInterface";
    protected $description = "Default GraphQL Interface Type";

    public function getResolveType()
    {
        return null;
    }
}

?>