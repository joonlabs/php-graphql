<?php

namespace GraphQL\Types;

use GraphQL\Errors\BadImplementationError;
use GraphQL\Errors\GraphQLError;

class GraphQLEnum extends GraphQLType
{
    protected $type = "Enum";
    protected $description = "Default GraphQL Enum Type";

    private $values = [];

    public function __construct(string $type, string $description, ?array $values)
    {
        $this->type = $type;
        $this->description = $description;

        // check for proper types and create value map
        foreach ($values as $v) {
            if (!$v instanceof GraphQLEnumValue) {
                throw new BadImplementationError(
                    "Enum values must be of type \"GraphQLEnumValue\""
                );
            } else {
                $this->values[$v->getName()] = $v;
            }
        }
    }

    public function serialize($outputValue)
    {
        if (!array_key_exists($outputValue, $this->values)) {
            throw new GraphQLError(
                "Value \"{$outputValue}\" does not exist in \"{$this->getName()}\" enum."
            );
        }

        return $outputValue;
    }

    public function parseLiteral($valueNode, $variables)
    {
        if ($valueNode["kind"] !== "EnumValue") {
            throw new GraphQLError(
                "Enum cannot represent non enum value: {$valueNode["value"]}"
            );
        }

        $value = $valueNode["value"];
        if (!array_key_exists($value, $this->values)) {
            throw new GraphQLError(
                "Value \"{$value}\" does not exist in \"{$this->getName()}\" enum."
            );
        }
        return $value;
    }

    public function parseValue($value)
    {
        if (!is_string($value)) {
            throw new GraphQLError(
                "Enum \"{$this->getName()}\" cannot represent non-string value: {$value}"
            );
        }

        if (!array_key_exists($value, $this->values)) {
            throw new GraphQLError(
                "Value \"{$value}\" does not exist in \"{$this->getName()}\" enum."
            );
        }

        return $value;
    }

    /**
     * @return array
     */
    public function getValues(): array
    {
        return $this->values;
    }
}

class GraphQLEnumValue
{

    private $id;
    private $description;
    private $deprecationReason;

    /**
     * GraphQLEnumValue constructor.
     * @param string $id
     * @param string $description
     * @param string|null $deprecationReason
     */
    public function __construct(string $id, string $description = "", ?string $deprecationReason = null)
    {
        $this->id = $id;
        $this->description = $description;
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
     * @return ?string
     */
    public function getDeprecationReason(): ?string
    {
        return $this->deprecationReason;
    }
}

?>