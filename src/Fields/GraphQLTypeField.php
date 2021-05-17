<?php

namespace GraphQL\Fields;

use Closure;
use GraphQL\Types\GraphQLType;

class GraphQLTypeField
{
    protected $id;
    private $args;
    private $type;
    private $description;
    private $resolve;
    private $defaultValue;
    private $deprecationReason;

    /**
     * GraphQLTypeField constructor.
     * @param string $id Id of the field
     * @param GraphQLType $type
     * @param string $description Description of the field
     * @param Closure|null $resolve The resolve function of the field, by defaukt returns the parentDataObject[$id]
     * @param array $args Arguments of the field
     * @param null $defaultValue Default value of field
     * @param string|null $deprecationReason Reason why field is deprecated
     */
    public function __construct(string $id, GraphQLType $type, string $description = "", Closure $resolve = null, array $args = [], $defaultValue = null, ?string $deprecationReason = null)
    {
        $this->id = $id;
        $this->type = $type;
        $this->description = $description;
        $this->args = $args;
        $this->resolve = $resolve;
        $this->defaultValue = $defaultValue ?? null;
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
     * @return null
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return string|null
     */
    public function getDeprecationReason(): ?string
    {
        return $this->deprecationReason;
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
     * @return Closure|null
     */
    public function getResolve(): ?Closure
    {
        return $this->resolve;
    }
}