<?php

namespace GraphQL\Types;

use GraphQL\Errors\GraphQLError;

/**
 * Class GraphQLFloat
 * @package GraphQL\Types
 */
class GraphQLFloat extends GraphQLScalarType
{
    protected $type = "Float";
    protected $description = "Default GraphQL Float Type";

    /**
     * @param $outputValue
     * @return float|null
     * @throws GraphQLError
     */
    public function serialize($outputValue): ?float
    {
        if (!is_float($outputValue) and $outputValue !== null) {
            throw new GraphQLError(
                "Value \"$outputValue\" is not of type \"{$this->getName()}\"."
            );
        }
        return $outputValue;
    }

    /**
     * @param $valueNode
     * @param $variables
     * @return float
     * @throws GraphQLError
     */
    public function parseLiteral($valueNode, $variables): float
    {
        if ($valueNode["kind"] !== "IntValue" and $valueNode["kind"] !== "FloatValue") {
            throw new GraphQLError(
                "Float cannot represent non numeric value: {$valueNode["value"]}"
            );
        }

        //check if is parsable as a float
        if (!is_numeric($valueNode["value"])) {
            throw new GraphQLError(
                "Value cannot be represent as a float value: {$valueNode["value"]}",
                $valueNode
            );
        }

        return floatval($valueNode["value"]);
    }

    /**
     * @param $value
     * @return float|null
     * @throws GraphQLError
     */
    public function parseValue($value): ?float
    {
        if (!is_float($value) and $value !== null) {
            throw new GraphQLError(
                "Value \"$value\" is not of type \"{$this->getName()}\"."
            );
        }
        return $value;
    }
}

