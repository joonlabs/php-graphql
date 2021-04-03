<?php

namespace GraphQL\Types;

class GraphQLUnion extends GraphQLAbstractType
{
    protected $type = "Union";
    protected $description = "Default GraphQL Union Type";

    private $types;
    private $resolveTypeFn;

    public function __construct(array $types, ?\Closure $resolveTypeFn=null)
    {
        $this->types = $types;
        $this->resolveTypeFn = $resolveTypeFn;
    }

    /**
     * @return array
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * @return \Closure|null
     */
    public function getResolveType(): ?\Closure
    {
        return $this->resolveTypeFn;
    }
}

?>