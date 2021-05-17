<?php

namespace GraphQL\Types;

use Closure;

class GraphQLInterface extends GraphQLAbstractType
{
    protected $type = "Interface";
    protected $description = "Default GraphQL Interface Type";

    private $fields;
    private $resolveTypeFn;

    public function __construct(\string $type, \string $description, Closure $fields, ?Closure $resolveTypeFn = null)
    {
        $this->type = $type;
        $this->description = $description;
        $this->fields = $fields;
        $this->resolveTypeFn = $resolveTypeFn;
    }

    /**
     * @return array
     */
    public function getFields(): array
    {
        $allFields = call_user_func($this->fields);
        $fields = [];
        foreach ($allFields as $field) {
            $fields[$field->getName()] = $field;
        }
        return $fields;
    }

    /**
     * @return Closure|null
     */
    public function getResolveType(): ?Closure
    {
        return $this->resolveTypeFn;
    }

    /**
     * @param Closure|null $resolveTypeFn
     * @return GraphQLInterface
     */
    public function setResolveTypeFn(?Closure $resolveTypeFn): GraphQLInterface
    {
        $this->resolveTypeFn = $resolveTypeFn;
        return $this;
    }

    /**
     * @return array
     */
    public function getInterfaces(): array
    {
        return [];
    }
}

