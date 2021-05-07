<?php

namespace GraphQL\Types;

class GraphQLUnion extends GraphQLAbstractType
{
    protected $type = "Union";
    protected $description = "Default GraphQL Union Type";

    private $types;
    private $resolveTypeFn;

    public function __construct(string $type, string $description, array $types, ?\Closure $resolveTypeFn=null)
    {
        $this->type = $type;
        $this->description = $description;
        $this->types = $types;
        $this->resolveTypeFn = $resolveTypeFn;
    }

    /**
     * @return array
     */
    public function getFields(): array
    {
        $fields = [];
        foreach($this->types as $type){
            $fields = array_merge($fields, $type->getFields());
        }
        return $fields;
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