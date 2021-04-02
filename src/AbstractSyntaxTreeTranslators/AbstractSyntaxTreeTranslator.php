<?php

namespace GraphQL\AbstractSyntaxTreeTranslators;

use GraphQL\Arguments\GraphQLQueryArgument;
use GraphQL\Errors\BadUserInputError;
use GraphQL\Fields\GraphQLMutation;
use GraphQL\Fields\GraphQLQuery;
use GraphQL\Fields\GraphQLQueryField;
use GraphQL\Variables\GraphQLVariableReference;

/***
 * This class translates an abstract syntax tree, created by a GraphQLQueryParser, into a GraphQLQueryField,
 * readably the QueryResolver.
 *
 * Class AbstractSyntaxTreeTranslator
 * @package src\AbstractSyntaxTreeTranslators
 */
class AbstractSyntaxTreeTranslator{
    /**
     * Takes an array in form of an abstract syntax tree and translates it into a query.
     *
     * @param array $abstractSyntaxTree
     * @return GraphQLQueryField
     */
    static public function translate(array $abstractSyntaxTree) : GraphQLQueryField
    {
        //TODO use VariableDefinitions

        $id = "root";
        $fields = AbstractSyntaxTreeTranslator::translateSelectionSet($abstractSyntaxTree["body"] ?? []);
        $alias = $abstractSyntaxTree["name"];
        $type = $abstractSyntaxTree["kind"];

        if($type==="QUERY"){
            return new GraphQLQuery(
                $id,
                $fields,
                [],
                $alias
            );
        }if($type==="MUTATION"){
            return new GraphQLMutation(
                $id,
                $fields,
                [],
                $alias
            );
        }

        throw new BadUserInputError("Unkown operation type. Supported operation types are \"query\" and \"mutation\".");
    }

    /**
     * Takes a selection set from the ast and turns it into list of GraphQLQueryField.
     *
     * @param array $selectionSet
     * @return array
     */
    private static function translateSelectionSet(array $selectionSet) : array
    {
        // see $selectionSet in AST
        $selectionListAST = $selectionSet["value"]["value"] ?? [];
        $selectionList = [];
        foreach($selectionListAST as $selection){
            $selectionList[] = AbstractSyntaxTreeTranslator::translateSelection($selection);
        }
        return $selectionList;
    }

    /**
     * Takes a selection selection from the ast and returns a GraphQLQueryField.
     *
     * @param array $selection
     * @return GraphQLQueryField
     */
    private static function translateSelection(array $selection) : GraphQLQueryField
    {
        $field = $selection["value"]["field"];
        return new GraphQLQueryField(
            $field["fieldName"]["value"],
            AbstractSyntaxTreeTranslator::translateSelectionSet($field["selectionSet"] ?? []),
            AbstractSyntaxTreeTranslator::translateArguments($field["arguments"] ?? []),
            $field["fieldAlias"]["value"] ?? null,
            $selection["loc"]
        );
    }

    /**
     * Takes an argument list and translates and returns a list of GraphQLQueryArgument.
     *
     * @param array $arguments
     * @return array
     */
    private static function translateArguments(array $arguments) : array
    {
        // see $arguments in AST
        $argumentsAST = $arguments["value"]["value"] ?? [];
        $arguments = [];

        foreach($argumentsAST as $argument){
            $arguments[] = new GraphQLQueryArgument(
                $argument["argument"]["argName"]["value"],
                self::translateValue($argument["argument"]["argValue"]),
                $argument["loc"]
            );
        }

        return $arguments;
    }

    /**
     * Takes different kind of values form the ast and returns the corresponding value.
     *
     * @param array $value
     * @return array|mixed|GraphQLVariableReference
     */
    private static function translateValue(array $value){
        $primitiveTypes = ["IntValue", "FloatValue", "StringValue", "BooleanValue", "NullValue", "EnumValue"];
        if(in_array($value["value"]["kind"], $primitiveTypes)){
            // is primitive type
            return $value["value"]["value"];
        }else {
            // is variable, list or object
            if($value["value"]["kind"]==="Variable"){
                // it is a variable
                return new GraphQLVariableReference(
                    $value["value"]["value"]["value"]
                );
            } else if($value["value"]["kind"]==="ListValue"){
                // it is a list
                $list = [];
                foreach($value["value"]["value"] as $listItem){
                    $list[] = $listItem["value"]["value"];
                }
                return $list;
            }else if($value["value"]["kind"]==="ObjectValue"){
                // it is an object
                $object = [];
                foreach($value["value"]["value"]["value"] as $objectItem){
                    $object[$objectItem["objectField"]["objName"]["value"]] = self::translateValue($objectItem["objectField"]["objValue"]);
                }
                return $object;
            }
        }
    }
}