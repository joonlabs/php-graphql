<?php

namespace GraphQL\Fields;

use GraphQL\Types\GraphQLType;

class GraphQLTypeField {
    protected $id;
    private $args;
    private $type;
    private $description;
    private $resolve;
    private $defaultValue;

    /**
     * GraphQLTypeField constructor.
     * @param string $id Id of the field
     * @param string $description Description of the field
     * @param array $args Arguments of the field
     * @param \Closure $resolve The resolve function of the field, by defaukt returns the parentDataObject[$id]
     */
    public function __construct(string $id, GraphQLType $type, string $description="", \Closure $resolve=null, array $args=[], $defaultValue=null)
    {
        $this->id = $id;
        $this->type = $type;
        $this->description = $description;
        $this->args = $args;
        $this->resolve = $resolve;
        $this->defaultValue = $defaultValue ?? null;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->id;
    }

    /**
     * @return null
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @param mixed|null $defaultValue
     */
    public function setDefaultValue($defaultValue): GraphQLTypeField
    {
        $this->defaultValue = $defaultValue;
        return $this;
    }

    /**
     * @return GraphQLType
     */
    public function getType(): GraphQLType
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function getArguments(): array
    {
        return $this->args;
    }

    /**
     * @return \Closure|null
     */
    public function getResolve(): ?\Closure
    {
        return $this->resolve;
    }
}