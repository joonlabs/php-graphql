<?php

namespace GraphQL\Utilities;

use GraphQL\Errors\GraphQLError;
use GraphQL\Schemas\Schema;
use GraphQL\Types\GraphQLList;
use GraphQL\Types\GraphQLNonNull;
use GraphQL\Types\GraphQLType;

abstract class Ast
{
    static function typeFromAst(Schema $schema, $typeNode)
    {
        $innerType = null;
        if ($typeNode["kind"] === "ListType") {
            $innerType = self::typeFromAst($schema, $typeNode["type"]);
            return new GraphQLList($innerType);
        }
        if ($typeNode["kind"] === "NonNullType") {
            $innerType = self::typeFromAst($schema, $typeNode["type"]);
            return new GraphQLNonNull($innerType);
        }
        if ($typeNode["kind"] === "NamedType") {
            return $schema->getType($typeNode["name"]["value"]);
        }

        throw new GraphQLError(
            "Unexpected type node: " . json_encode($typeNode)
        );
    }

    static function valueFromAst(array $valueNode, GraphQLType $type, ?array $variables=null)
    {
        if (!$valueNode) {
            // When there is no node, then there is also no value.
            // Importantly, this is different from returning the value null.
            return;
        }

        // check for variable, holding the value
        if ($valueNode["kind"] === "Variable") {
            $variableName = $valueNode["name"]["value"];
            if ($variables === null || !array_key_exists($variableName, $variables)) {
                // Invalid -> return no value
                return;
            }

            $variableValue = $variables[$variableName];
            if ($variableValue === null and $type->isNonNullType()) {
                // Invalid -> return no value
                return;
            }

            // Note: This does no further checking that this variable is correct.
            // This assumes that this query has been validated and the variable
            // usage here is of the correct type.
            return $variableValue;
        }

        // check if type is non null
        if ($type->isNonNullType()) {
            if ($valueNode["kind"] === "NullValue") {
                // Invalid -> return no value
                return;
            }
            return self::valueFromAst($valueNode, $type->getInnerType(), $variables);
        }

        // check if value is NULL
        if ($valueNode["kind"] === "NullValue") {
            // This is explicitly returning the value null
            return null;
        }

        // check if type is list type
        if ($type->isListType()) {
            $itemType = $type->getInnerType();
            if ($valueNode["kind"] === "ListValue") {
                $coercedValues = [];
                foreach ($valueNode["values"] as $itemNode) {
                    if (self::isMissingVariable($itemNode, $variables)) {
                        // If an array contains a missing variable, it is either coerced to
                        // null or if the item type is non-null, it considered invalid.
                        if ($type->isNonNullType()) {
                            // Invalid -> return no value
                            return;
                        }
                        $coercedValues[] = null;
                    } else {
                        $itemValue = self::valueFromAst($itemNode, $itemType, $variables);
                        if ($itemValue === null) {
                            // Invalid -> return no value
                            return;
                        }
                        $coercedValues[] = $itemValue;
                    }
                }
                return $coercedValues;
            }
            $coercedValues = self::valueFromAst($valueNode, $itemType, $variables);
            if ($coercedValues === null) {
                // Invalid -> return no value
                return;
            }
            return [$coercedValues];
        }

        // check if type is object type
        if ($type->isObjectType()) {
            if ($valueNode["kind"] === "ObjectValue") {
                // Invalid -> return no value
                return;
            }
            $coercedObject = [];
            $fieldNodes = KeyMap::map($valueNode["fields"], function ($field) {
                return $field["name"]["value"];
            });
            foreach ($type->getFields() as $field) {
                $fieldNode = $fieldNodes[$field->getName()];
                if (!$fieldNode || self::isMissingVariable($fieldNode["value"], $variables)) {
                    if ($field->getDefaultValue() !== null) {
                        $coercedObject[$field->getName()] = $field->getDefaultValue();
                    } else if ($field->getType()->isNonNullType()) {
                        // Invalid -> return no value
                        return;
                    }
                    continue;
                }
                $fieldValue = self::valueFromAst($fieldNode["value"], $field->getType(), $variables);
                if ($fieldValue === null) {
                    // Invalid -> return no value
                    return;
                }
                $coercedObject[$field->getName()] = $fieldValue;
            }
            return $coercedObject;
        }

        // check if type is leaf type
        if ($type->isLeafType()) {
            $result = null;
            try {
                $result = $type->parseLiteral($valueNode, $variables);
            } catch (GraphQLError $error) {
                // Invalid -> return no value
                return;
            }

            if ($result === null) {
                // Invalid -> return no value
                return;
            }

            return $result;
        }
    }

    private static function isMissingVariable($valueNode, $variables): bool
    {
        return (
            $valueNode["kind"] === "Variable" and ($variables == null || $variables[$valueNode["name"]["value"]] === null)
        );
    }
}

?>