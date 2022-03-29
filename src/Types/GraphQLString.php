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
        if (!$this->isStringableValue($outputValue)) {
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
        if (!$this->isStringableValue($value)) {
            // cast value to a printable version
            $value = is_array($value)
                ? "[Array]"
                : (string)$value;

            throw new GraphQLError(
                "Value \"$value\" is not of type \"{$this->getName()}\"."
            );
        }
        return $value;
    }

    /**
     * Returns whether the object can be casted to a string or not.
     *
     * @param mixed $value
     * @return bool
     */
    private function isStringableValue(mixed $value): bool
    {
        return $value === null
            || is_string($value)
            || is_object($value) && method_exists($value, '__toString');
    }
}

