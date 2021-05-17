<?php

namespace GraphQL\Types;

use GraphQL\Errors\BadImplementationError;
use GraphQL\Errors\GraphQLError;

/**
 * Class GraphQLEnum
 * @package GraphQL\Types
 */
class GraphQLEnum extends GraphQLType
{
    protected $type = "Enum";
    protected $description = "Default GraphQL Enum Type";

    private $values = [];

    /**
     * GraphQLEnum constructor.
     * @param string $type
     * @param string $description
     * @param array|null $values
     * @throws BadImplementationError
     */
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

    /**
     * @param $outputValue
     * @return mixed
     * @throws GraphQLError
     */
    public function serialize($outputValue)
    {
        if (!array_key_exists($outputValue, $this->values)) {
            throw new GraphQLError(
                "Value \"$outputValue\" does not exist in \"{$this->getName()}\" enum."
            );
        }

        return $outputValue;
    }

    /**
     * @param $valueNode
     * @param $variables
     * @return mixed
     * @throws GraphQLError
     */
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
                "Value \"$value\" does not exist in \"{$this->getName()}\" enum."
            );
        }
        return $value;
    }

    /**
     * @param $value
     * @return string
     * @throws GraphQLError
     */
    public function parseValue($value): string
    {
        if (!is_string($value)) {
            throw new GraphQLError(
                "Enum \"{$this->getName()}\" cannot represent non-string value: $value"
            );
        }

        if (!array_key_exists($value, $this->values)) {
            throw new GraphQLError(
                "Value \"$value\" does not exist in \"{$this->getName()}\" enum."
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

