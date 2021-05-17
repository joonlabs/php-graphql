<?php

namespace GraphQL\Types;

use GraphQL\Errors\GraphQLError;

/**
 * Class GraphQLString
 * @package GraphQL\Types
 */
class GraphQLString extends GraphQLScalarType
{
    protected $type = "String";
    protected $description = "Default GraphQL String Type";

    /**
     * @param $outputValue
     * @return string|null
     * @throws GraphQLError
     */
    public function serialize($outputValue): ?string
    {
        if (!is_string($outputValue) and $outputValue !== null) {
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
        if ($valueNode["kind"] !== "StringValue") {
            throw new GraphQLError(
                "String cannot represent a non string value: {$valueNode["value"]}"
            );
        }

        return $valueNode["value"];
    }

    /**
     * @param $value
     * @return string|null
     * @throws GraphQLError
     */
    public function parseValue($value): ?string
    {
        if (!is_string($value) and $value !== null) {
            throw new GraphQLError(
                "Value \"$value\" is not of type \"{$this->getName()}\"."
            );
        }
        return $value;
    }
}

