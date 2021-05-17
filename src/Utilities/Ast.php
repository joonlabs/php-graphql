<?php

namespace GraphQL\Utilities;

use GraphQL\Errors\GraphQLError;
use GraphQL\Errors\ValidationError;
use GraphQL\Internals\UndefinedValue;
use GraphQL\Schemas\Schema;
use GraphQL\Types\GraphQLList;
use GraphQL\Types\GraphQLNonNull;
use GraphQL\Types\GraphQLType;

abstract class Ast
{
    static function typeFromAst(Schema $schema, $typeNode)
    {
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

    static function valueFromAst(array $valueNode, GraphQLType $type, ?array $variables = null)
    {
        if (!$valueNode) {
            // When there is no node, then there is also no value.
            // Importantly, this is different from returning the value null.
            return new UndefinedValue();
        }

        // check for variable, holding the value
        if ($valueNode["kind"] === "Variable") {
            $variableName = $valueNode["name"]["value"];
            if ($variables === null || !array_key_exists($variableName, $variables)) {
                // Invalid -> return no value
                return new UndefinedValue();
            }

            $variableValue = $variables[$variableName];
            if ($variableValue === null and $type->isNonNullType()) {
                // Invalid -> return no value
                return new UndefinedValue();
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
                return new UndefinedValue();
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
                            return new UndefinedValue();
                        }
                        $coercedValues[] = null;
                    } else {
                        $itemValue = self::valueFromAst($itemNode, $itemType, $variables);
                        if ($itemValue instanceof UndefinedValue) {
                            // Invalid -> return no value
                            return new UndefinedValue();
                        }
                        $coercedValues[] = $itemValue;
                    }
                }
                return $coercedValues;
            }
            // supports non-list values? (see: https://github.com/graphql/graphql-js/blob/main/src/utilities/valueFromAST.js#L98)
            $coercedValues = self::valueFromAst($valueNode, $itemType, $variables);
            if ($coercedValues instanceof UndefinedValue) {
                // Invalid -> return no value
                return new UndefinedValue();
            }
            return [$coercedValues];
        }

        // check if type is object type
        if ($type->isInputObjectType()) {
            if ($valueNode["kind"] !== "ObjectValue") {
                // Invalid -> return no value
                return new UndefinedValue();
            }
            $coercedObject = [];
            $fieldNodes = KeyMap::map($valueNode["fields"] ?? [], function ($field) {
                return $field["name"]["value"];
            });

            // Implements the rule specified under 5.6.3 (Input Object Field Uniqueness) in the GraphQL-Specs (version: 2018)
            if (count($valueNode["fields"] ?? []) !== count($fieldNodes)) {
                // before mapping to keys and after mapping to keys there is a different amount of items
                // which means, there must have been some values provided at least twice
                throw new ValidationError(
                    "Input objects must not contain more than one field of the same name."
                );
            }

            foreach ($type->getFields() as $field) {
                $fieldNode = $fieldNodes[$field->getName()] ?? null;
                if ($fieldNode === null || self::isMissingVariable($fieldNode["value"], $variables)) {
                    if ($field->getDefaultValue() !== null) {
                        $coercedObject[$field->getName()] = $field->getDefaultValue();
                    } else if ($field->getType()->isNonNullType()) {
                        // Invalid -> return no value
                        return new UndefinedValue();
                    }
                    continue;
                }
                $fieldValue = self::valueFromAst($fieldNode["value"], $field->getType(), $variables);
                if ($fieldValue instanceof UndefinedValue) {
                    // Invalid -> return no value
                    return new UndefinedValue();
                }
                $coercedObject[$field->getName()] = $fieldValue;
            }

            // check if there are fields provided that cannot be applied to this input type
            // implements the rule specified under 5.6.2 (Input Object Field Names) in the GraphQL-Specs (version: 2018)
            $fieldsInNode = array_keys($fieldNodes);
            $fieldsInType = array_keys($type->getFields());
            $sameFields = array_diff($fieldsInNode, $fieldsInType);
            if (count($sameFields) > 0 and count($fieldsInNode) > count($fieldsInType)) {
                throw new ValidationError(
                    "The following input fields are provided in the input object value but not defined as field in the input object's type: \"" . implode($sameFields, "\", \"") . "\""
                );
            }

            return $coercedObject;
        }

        // check if type is leaf type
        if ($type->isLeafType()) {
            try {
                $result = $type->parseLiteral($valueNode, $variables);
            } catch (GraphQLError $error) {
                // Invalid -> return no value
                return new UndefinedValue();
            }

            if ($result === null) {
                // Invalid -> return no value
                return new UndefinedValue();
            }

            return $result;
        }

        // default to undefined value
        return new UndefinedValue();
    }

    private static function isMissingVariable($valueNode, $variables): bool
    {
        return (
            $valueNode["kind"] === "Variable" and ($variables == null || $variables[$valueNode["name"]["value"]] === null)
        );
    }
}
