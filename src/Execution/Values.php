<?php

namespace GraphQL\Execution;

use GraphQL\Directives\GraphQLDirective;
use GraphQL\Errors\GraphQLError;
use GraphQL\Errors\ValidationError;
use GraphQL\Internals\UndefinedValue;
use GraphQL\Schemas\Schema;
use GraphQL\Utilities\Ast;
use GraphQL\Utilities\InputValues;
use GraphQL\Utilities\KeyMap;

/**
 * Class Values
 * @package GraphQL\Execution
 */
abstract class Values
{
    /**
     * @param Schema $schema
     * @param array $variableDefinitions
     * @param array $inputs
     * @return array
     */
    public static function getVariableValues(Schema $schema, array $variableDefinitions, array $inputs): array
    {
        $errors = [];
        try {
            return self::coerceVariableValues($schema, $variableDefinitions, $inputs);
        } catch (GraphQLError $error) {
            $errors[] = $error;
        }
        return $errors;
    }

    /**
     * @param Schema $schema
     * @param array $variableDefinitions
     * @param array $inputs
     * @return array
     * @throws GraphQLError
     * @throws \GraphQL\Errors\ValidationError
     */
    public static function coerceVariableValues(Schema $schema, array $variableDefinitions, array $inputs): array
    {
        $coercedValues = [];
        foreach ($variableDefinitions as $varDefNode) {
            $varName = $varDefNode["variable"]["name"]["value"];
            $varType = Ast::typeFromAst($schema, $varDefNode["type"]);

            // check if type is an inputType
            if (!$varType->isInputType()) {
                $varTypeStr = $varType->getName();
                throw new GraphQLError(
                    "Variable \"$varName\" expected value of type \"$varTypeStr\" which cannot be used as an input type."
                );
            }

            // check if variable exists in inputs or has a default value
            if (!array_key_exists($varName, $inputs)) {
                if ($varDefNode["defaultValue"] !== null) {
                    $coercedValues[$varName] = Ast::valueFromAst($varDefNode["defaultValue"], $varType);
                } else if ($varType->isNonNullType()) {
                    $varTypeStr = $varType->getName();
                    throw new GraphQLError(
                        "Variable \"$varName\" of required type \"$varTypeStr\" was not provided."
                    );
                }
                continue;
            }

            // check if value is null but must not be so
            $value = $inputs[$varName] ?? null;
            if ($value === null and $varType->isNonNullType()) {
                $varTypeStr = $varType->getName();
                throw new GraphQLError(
                    "Variable \"$varName\" of non-null type \"$varTypeStr\" must not be null."
                );
            }

            // coerce value
            try{
                $coercedValues[$varName] = InputValues::coerceInputValue(
                    $value,
                    $varType
                );
            }catch (GraphQLError $error){
                // add node to error when coercing input variable failed
                throw new ValidationError(
                    "Cannot coerce input value of type \"{$varType->getName()}\"." .
                    $error->getMessage(),
                    $varDefNode);
            }

        }
        return $coercedValues;
    }

    /**
     * @param GraphQLDirective $directiveDef
     * @param $node
     * @param $variableValues
     * @return array|null
     * @throws GraphQLError
     */
    public static function getDirectiveValues(GraphQLDirective $directiveDef, $node, $variableValues): ?array
    {
        $directiveNode = array_filter($node["directives"], function ($directive) use ($directiveDef) {
                return $directive["name"]["value"] == $directiveDef->getName();
            })[0] ?? null;

        if ($directiveNode) {
            return self::getArgumentValues($directiveDef, $directiveNode, $variableValues);
        }

        // default to null
        return null;
    }

    /**
     * @param $def
     * @param $node
     * @param $variableValues
     * @return array
     * @throws GraphQLError
     * @throws \GraphQL\Errors\ValidationError
     */
    public static function getArgumentValues($def, $node, $variableValues): array
    {
        $coercedValues = [];

        $argumentNodes = $node["arguments"] ?? [];

        $argNodeMap = KeyMap::map($argumentNodes, function ($arg) {
            return $arg["name"]["value"];
        });

        foreach ($def->getArguments() as $argDef) {
            $name = $argDef->getName();
            $argType = $argDef->getType();
            $argumentNode = $argNodeMap[$name] ?? null;

            // if no argument specified in AST, check if default argument exists and is valid
            if ($argumentNode === null) {
                if ($argDef->getDefaultValue() !== null) {
                    $coercedValues[$name] = $argDef->getDefaultValue();
                } else if ($argType->isNonNullType()) {
                    throw new GraphQLError(
                        "Argument \"$name\" of required type \"{$argType->getName()}\" was not provided.'"
                    );
                }
                continue;
            }

            $valueNode = $argumentNode["value"];
            $isNull = $valueNode["kind"] === "NullValue";

            // if argument variable is specified
            if ($valueNode["kind"] === "Variable") {
                $variableName = $valueNode["name"]["value"];
                if ($variableName === null || !array_key_exists($variableName, $variableValues)) {
                    if ($argDef->getDefaultValue() !== null) {
                        $coercedValues[$name] = $argDef->getDefaultValue();
                    } else if ($argType->isNonNullType()) {
                        throw new GraphQLError(
                            "Argument \"$name\" of required type \"{$argType->getName()}\" was provided the variable \"$variableName\" which was not provided a runtime value."
                        );
                    }
                    continue;
                }
                $isNull = $variableValues[$variableName] === null;
            }

            // check if argument is null but most not be so
            if ($isNull and $argType->isNonNullType()) {
                throw new GraphQLError(
                    "Argument \"$name\" of non-null type \"{$argType->getName()}\" must not be null."
                );
            }

            $coercedValue = Ast::valueFromAst($valueNode, $argType, $variableValues);
            if ($coercedValue instanceof UndefinedValue) {
                throw new GraphQLError(
                    "Argument \"$name\" of type \"{$argType->getName()}\" has invalid value. Maybe provided wrong type or forgot non-nullable field in input object?"
                );
            }
            $coercedValues[$name] = $coercedValue;
        }
        return $coercedValues;
    }
}

