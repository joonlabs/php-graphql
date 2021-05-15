<?php

namespace GraphQL\Arguments;

use GraphQL\Types\GraphQLType;

abstract class GraphQLArgument
{
    protected $id;
    protected $type;

    public function __construct(string $id, GraphQLType $type, string $description = "", $defaultValue = null, ?string $deprecationReason = null)
    {
        $this->id = $id;
        $this->description = $description;
        $this->type = $type;
        $this->defaultValue = $defaultValue;
        $this->deprecationReason = $deprecationReason ?? null;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return GraphQLType
     */
    public function getType(): GraphQLType
    {
        return $this->type;
    }

    /**
     * @return string|null
     */
    public function getDeprecationReason(): ?string
    {
        return $this->deprecationReason;
    }

    /**
     * @return null
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }
}