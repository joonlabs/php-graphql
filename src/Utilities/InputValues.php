<?php

namespace GraphQL\Utilities;

use GraphQL\Errors\GraphQLError;
use GraphQL\Types\GraphQLType;

abstract class InputValues
{
    static function coerceInputValue($inputValue, GraphQLType $type, $path = [])
    {
        // check for null type
        if ($type->isNonNullType()) {
            if ($inputValue !== null) {
                return self::coerceInputValue($inputValue, $type->getInnerType(), $path);
            }
            throw new GraphQLError(
                "Expected non-nullable type \"{$type->getName()}\" not to be null."
            );
        }

        // check for null value
        if ($inputValue === null) {
            return null;
        }

        // check for list type
        if ($type->isListType()) {
            $itemType = $type->getInnerType();
            if (is_iterable($inputValue)) {
                return array_map(function ($itemValue, $index) use ($itemType, $path) {
                    $itemPath = [$path, $index, null];
                    return self::coerceInputValue($itemValue, $itemType, $itemPath);
                }, $inputValue, array_keys($inputValue));
            }
            // Lists accept a non-list value as a list of one
            return [self::coerceInputValue($inputValue, $itemType, $path)];
        }

        // check for object type
        if ($type->isInputObjectType()) {
            if (!is_array($inputValue) and $inputValue !== null) {
                throw new GraphQLError(
                    "Expected type \"{$type->getName()}\" to be an object."
                );
            }

            $coercedValue = [];
            $fieldDefs = $type->getFields();

            // coerce values for each field
            foreach ($fieldDefs as $field) {
                $fieldValue = $inputValue[$field->getName()] ?? null;

                // if field-value was not provided, check iof that is allowed
                if (!array_key_exists($field->getName(), $inputValue)) {
                    $defaultValue = $field->getDefaultValue();
                    if ($field->getType()->isNonNullType() and $defaultValue === null) {
                        throw new GraphQLError(
                            "Field \"{$field->getName()}\" of required type \"{$field->getType()->getName()}\" was not provided."
                        );
                    } else {
                        $coercedValue[$field->getName()] = $defaultValue;
                    }
                    continue;
                }

                $coercedValue[$field->getName()] = self::coerceInputValue($fieldValue, $field->getType(), [$path, $field->getName(), $type->getName()]);
            }

            // ensure every provided field is defined
            foreach (array_keys($inputValue) as $fieldName) {
                if (!array_key_exists($fieldName, $fieldDefs)) {
                    $suggestions = Suggestions::suggest($fieldName, array_keys($type->getFields()));
                    throw new GraphQLError(
                        "Field \"{$fieldName}\" is not defined by type \"{$type->getName()}\"." . Suggestions::didYouMean($suggestions)
                    );
                }
            }
            return $coercedValue;
        }

        // check for leaf types
        if ($type->isLeafType()) {
            $parseResult = null;

            // Scalars and Enums determine if an input value is valid via parseValue(),
            // which can throw to indicate failure. If it throws, maintain a reference
            // to the original error.

            // check for other scalar types?? (see: https://github.com/graphql/graphql-js/blob/5ed55b89d526c637eeb9c440715367eec8a2adec/src/utilities/coerceInputValue.js#L149)
            $parseResult = $type->parseValue($inputValue);

            return $parseResult;
        }
    }
}

?>