<?php

namespace GraphQL\Types;

use GraphQL\Errors\GraphQLError;

class GraphQLInt extends GraphQLScalarType
{
    protected $type = "Int";
    protected $description = "Default GraphQL Integer Type";

    /**
     * @param $outputValue
     * @return int|null
     * @throws GraphQLError
     */
    public function serialize($outputValue): ?int
    {
        if (!is_int($outputValue) and $outputValue !== null) {
            throw new GraphQLError(
                "Value \"$outputValue\" is not of type \"{$this->getName()}\"."
            );
        }
        return $outputValue;
    }

    /**
     * @param $valueNode
     * @param $variables
     * @return int
     * @throws GraphQLError
     */
    public function parseLiteral($valueNode, $variables): int
    {
        if ($valueNode["kind"] !== "IntValue") {
            throw new GraphQLError(
                "Int cannot represent non-integer value: {$valueNode["value"]}"
            );
        }

        $num = intval($valueNode["value"]);

        //check for 32 bit integer
        if ($num > 2147483647 || $num < -2147483648) {
            throw new GraphQLError(
                "Int cannot represent non 32-bit signed integer value: {$valueNode["value"]}",
                $valueNode
            );
        }
        return $num;
    }

    /**
     * @param $value
     * @return int|null
     * @throws GraphQLError
     */
    public function parseValue($value): ?int
    {
        if (!is_int($value) and $value !== null) {
            throw new GraphQLError(
                "Value \"$value\" is not of type \"{$this->getName()}\"."
            );
        }
        return $value;
    }
}

