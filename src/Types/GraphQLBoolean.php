<?php

namespace GraphQL\Types;

use GraphQL\Errors\GraphQLError;

/**
 * Class GraphQLBoolean
 * @package GraphQL\Types
 */
class GraphQLBoolean extends GraphQLScalarType
{
    protected $type = "Boolean";
    protected $description = "Default GraphQL Boolean Type";

    /**
     * @param $outputValue
     * @return bool|null
     * @throws GraphQLError
     */
    public function serialize($outputValue): ?bool
    {
        if (!is_bool($outputValue) and $outputValue !== null) {
            throw new GraphQLError(
                "Value \"$outputValue\" is not of type \"{$this->getName()}\"."
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
        if ($valueNode["kind"] !== "BooleanValue") {
            throw new GraphQLError(
                "Boolean cannot represent non boolean value: {$valueNode["value"]}"
            );
        }

        return $valueNode["value"];
    }

    /**
     * @param $value
     * @return bool|null
     * @throws GraphQLError
     */
    public function parseValue($value): ?bool
    {
        if (!is_bool($value) and $value !== null) {
            throw new GraphQLError(
                "Value \"$value\" is not of type \"{$this->getName()}\"."
            );
        }
        return $value;
    }
}

