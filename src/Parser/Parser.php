<?php

namespace GraphQL\Parser;

use GraphQL\Errors\BadUserInputError;
use GraphQL\Errors\UnexpectedEndOfInputError;
use GraphQL\Errors\UnexpectedTokenError;

class Parser
{
    private $tokenizer;
    private $string = "";
    private $lookahead;

    public function __construct()
    {
        $this->string = "";
        $this->tokenizer = new Tokenizer();
    }

    /**
     * Returns the abstract syntax tree corresponding to the a given query or mutation.
     *
     * @param $string
     * @return array
     * @throws UnexpectedTokenError
     */
    public function parse($string)
    {
        $this->string = $string;
        $this->tokenizer->init($string);

        // prime the tokenizer to obtain the first
        // token which is out lookahead. The lookahead
        // is used for predictive parsing.

        $this->lookahead = $this->tokenizer->getNextToken();

        // entry point for parsing
        return $this->Document();
    }

    /***
     * Document
     *  : ExecutableDefinitionList
     *
     * Notice: ExecutableDefinitionList is just a shorthand as the DDL-lang is not supported
     */
    public function Document()
    {
        $location = $this->tokenizer->getLocation();
        return [
            "kind" => "Document",
            "definitions" => $this->ExecutableDefinitionList(),
            "loc" => $location,
        ];
    }

    /***
     * ExecutableDefinitionList
     *  : ExecutableDefinitionList
     *  : ExecutableDefinition
     */
    public function ExecutableDefinitionList()
    {
        $executableDefinitionList = [];

        while ($this->lookahead !== null) {
            $executableDefinitionList[] = $this->ExecutableDefinition();
        }

        return $executableDefinitionList;
    }

    /***
     * ExecutableDefinition
     *  : OperationDefinition
     *  : FragmentDefinition
     */
    public function ExecutableDefinition()
    {
        if ($this->lookahead["type"] === "FRAGMENT") {
            return $this->FragmentDefinition();
        } else {
            return $this->OperationDefinition();
        }
    }

    /***
     * OperationDefinition
     *  : SelectionSet
     *  : query SelectionSet
     *  : query Name VariableDefinitions SelectionSet
     *  : mutation SelectionSet
     *  : mutation Name VariableDefinitions SelectionSet
     */
    public function OperationDefinition()
    {
        $type = "query";
        $name = null;
        $variableDefinitions = [];
        $directives = [];

        $location = $this->tokenizer->getLocation();

        if ($this->lookahead["type"] != "{") {
            $token = $this->eat($this->lookahead["type"]); // eat QUERY / MUTATION / SUBSCRIPTION
            $type = strtolower($token["type"]);
            if ($this->lookahead["type"] == "NAME") {
                // query is named and has parameters
                $name = $this->Name();
                $variableDefinitions = $this->lookahead["type"] == "(" ? $this->VariableDefinitions() : [];
                $directives = $this->lookahead["type"] === "@" ? $this->Directives() : [];
            }
        }

        return [
            "kind" => "OperationDefinition",
            "operation" => $type,
            "name" => $name,
            "variableDefinitions" => $variableDefinitions,
            "directives" => $directives,
            "selectionSet" => $this->SelectionSet(),
            "loc" => $location
        ];
    }

    /***
     * FragmentDefinition
     *  : fragment FragmentName TypeCondition SelectionSet
     */
    public function FragmentDefinition()
    {
        $location = $this->tokenizer->getLastLocation();
        $this->eat("FRAGMENT");
        $name = $this->Name();
        $typeCondition = $this->TypeCondition();
        $directives = [];
        if ($this->lookahead["type"] == "@") {
            $directives = $this->Directives();
        }
        $selectionSet = $this->SelectionSet();
        return [
            "kind" => "FragmentDefinition",
            "name" => $name,
            "typeCondition" => $typeCondition,
            "directives" => $directives,
            "selectionSet" => $selectionSet,
            "loc" => $location
        ];
    }


    /***
     * SelectionSet
     *  : { SelectionList }
     */
    public function SelectionSet()
    {
        $location = $this->tokenizer->getLastLocation();
        $this->eat("{");
        $selectionList = $this->SelectionList();
        $this->eat("}");
        return [
            "kind" => "SelectionSet",
            "selections" => $selectionList,
            "loc" => $location
        ];
    }

    /***
     * SelectionList
     *  : SelectionList
     *  : Selection
     */
    public function SelectionList()
    {
        $selectionList = [$this->Selection()];

        while ($this->lookahead["type"] !== "}") {
            $selectionList[] = $this->Selection();
        }

        return $selectionList;
    }

    /**
     * Selection
     *  : Field
     *  : FagmentSpread
     */
    public function Selection()
    {
        if ($this->lookahead["type"] !== "...") {
            return $this->Field();
        } else {
            // get temporarly the second lookeahead
            $secondLookahead = $this->tokenizer->glimpsAtNextToken();
            // check if fragmentspread or inline fragment
            if (
                $secondLookahead["type"] === "ON" or
                $secondLookahead["type"] === "@" or
                $secondLookahead["type"] === "{"
            ) {
                return $this->InlineFragment();
            } else {
                return $this->FragmentSpread();
            }

        }
    }

    /**
     * Field
     *  : Alias? : Name Arguments? SelectionSet?
     */
    public function Field()
    {
        $alias = null;
        $name = null;
        $arguments = [];
        $directives = [];
        $selectionSet = [];

        $location = $this->tokenizer->getLastLocation();

        // determine if alias is used
        $nameOrAlias = $this->Name();
        if ($this->lookahead["type"] == ":") {
            $alias = $nameOrAlias;
            $this->eat(":");
            $name = $this->Name();
        } else {
            $name = $nameOrAlias;
        }

        // determine if arguments are used
        if ($this->lookahead["type"] == "(") {
            $arguments = $this->Arguments();
        }

        // dtermine if directives are used
        if ($this->lookahead["type"] == "@") {
            $directives = $this->Directives();
        }

        // determine if selectionset is used
        if ($this->lookahead["type"] == "{") {
            $selectionSet = $this->SelectionSet();
        }

        return [
            "kind" => "Field",
            "alias" => $alias,
            "name" => $name,
            "arguments" => $arguments,
            "directives" => $directives,
            "selectionSet" => $selectionSet,
            "loc" => $location
        ];
    }

    /***
     * VariableDefinitions
     *  : ( VariableDefinitionList )
     */
    public function VariableDefinitions()
    {

        $this->eat("(");
        $variableDefinitionList = $this->VariableDefinitionList();
        $this->eat(")");
        return $variableDefinitionList;
    }

    /***
     * VariableDefinitionList
     *  : VariableDefinition
     *  : VariableDefinitionList
     */
    public function VariableDefinitionList()
    {
        $variableDefinitionList = [$this->VariableDefinition()];

        while ($this->lookahead["type"] !== ")") {
            $this->eat(",");
            $variableDefinitionList[] = $this->VariableDefinition();
        }

        return $variableDefinitionList;
    }

    /***
     * VariableDefinition
     *  : Variable : Type
     *  : Variable : Type DefaultValue
     */
    public function VariableDefinition()
    {
        $location = $this->tokenizer->getLastLocation();
        $variable = $this->Variable();
        $this->eat(":");
        $type = $this->Type();
        $defaultValue = null;
        if ($this->lookahead["type"] === "=") {
            $defaultValue = $this->DefaultValue();
        }
        return [
            "kind" => "VariableDefinition",
            "variable" => $variable,
            "type" => $type,
            "defaultValue" => $defaultValue,
            "directives" => [],
            "loc" => $location
        ];
    }

    /***
     * Variable
     *  : $ Name
     */
    public function Variable()
    {
        $location = $this->tokenizer->getLastLocation();
        $this->eat("$");
        return [
            "kind" => "Variable",
            "name" => $this->Name(),
            "loc" => $location
        ];
    }

    /***
     * DefaultValue
     *  : = Values
     */
    public function DefaultValue()
    {
        $this->eat("=");
        return $this->Value();
    }

    /***
     * Values
     *  : Variable
     *  : IntValue
     *  : FloatValue
     *  : StringValue
     *  : BooleanValue
     *  : NullValue
     *  : EnumValue
     *  : ListValue
     *  : ObjectValue
     */
    public function Value()
    {
        $value = null;
        $location = $this->tokenizer->getLastLocation();
        if ($this->lookahead["type"] == "$") $value = $this->Variable();
        else if ($this->lookahead["type"] == "INTEGER") $value = $this->IntValue();
        else if ($this->lookahead["type"] == "FLOAT") $value = $this->FloatValue();
        else if ($this->lookahead["type"] == "STRING") $value = $this->StringValue();
        else if ($this->lookahead["type"] == "BOOLEAN") $value = $this->BooleanValue();
        else if ($this->lookahead["type"] == "NULL") $value = $this->NullValue();
        else if ($this->lookahead["type"] == "NAME") $value = $this->EnumValue();
        else if ($this->lookahead["type"] == "[") $value = $this->ListValue();
        else if ($this->lookahead["type"] == "{") $value = $this->ObjectValue();

        if ($value === null) throw new UnexpectedTokenError("Unexpected token: \"" . $this->lookahead["value"] . "\", expected Values", $location);

        return $value;
    }

    /***
     * Primitive Values
     */
    public function IntValue()
    {
        $location = $this->tokenizer->getLastLocation();
        return [
            "kind" => "IntValue",
            "value" => intval($this->eat("INTEGER")["value"]),
            "loc" => $location
        ];
    }

    public function FloatValue()
    {
        $location = $this->tokenizer->getLastLocation();
        return [
            "kind" => "FloatValue",
            "value" => floatval($this->eat("FLOAT")["value"]),
            "loc" => $location
        ];
    }

    public function StringValue()
    {
        $location = $this->tokenizer->getLastLocation();
        return [
            "kind" => "StringValue",
            "value" => substr($this->eat("STRING")["value"], 1, -1),
            "loc" => $location
        ];
    }

    public function BooleanValue()
    {
        $location = $this->tokenizer->getLastLocation();
        return [
            "kind" => "BooleanValue",
            "value" => $this->eat("BOOLEAN")["value"] == "true",
            "loc" => $location
        ];
    }

    public function NullValue()
    {
        $location = $this->tokenizer->getLastLocation();
        $this->eat("NULL")["value"];
        return [
            "kind" => "NullValue",
            "value" => null,
            "loc" => $location
        ];
    }

    public function EnumValue()
    {
        $location = $this->tokenizer->getLastLocation();
        $value = $this->Name()["value"];
        return [
            "kind" => "EnumValue",
            "value" => $value,
            "loc" => $location
        ];
    }

    /***
     * ListValue
     *  : [ Values?... ]
     */
    public function ListValue()
    {
        $location = $this->tokenizer->getLastLocation();

        $this->eat("[");

        $valueList = [$this->Value()];

        while ($this->lookahead["type"] !== "]") {
            $this->eat(",");
            $valueList[] = $this->Value();
        }

        $this->eat("]");

        return [
            "kind" => "ListValue",
            "values" => $valueList,
            "loc" => $location
        ];
    }

    /***
     * ObjectValue
     *  : { ObjectFieldList }
     */
    public function ObjectValue()
    {
        $location = $this->tokenizer->getLastLocation();

        $this->eat("{");

        $objectFieldList = null;
        if ($this->lookahead["type"] !== "}") {
            $objectFieldList = $this->ObjectFieldList();
        }

        $this->eat("}");

        return [
            "kind" => "ObjectValue",
            "fields" => $objectFieldList,
            "loc" => $location
        ];
    }

    /***
     * ObjectFieldList
     *  : ObjectField
     *  : ObjectField, ObjectFieldList
     */
    public function ObjectFieldList()
    {
        $objectFieldList = [$this->ObjectField()];

        while ($this->lookahead["type"] !== "}") {
            $this->eat(",");
            $objectFieldList[] = $this->ObjectField();
        }

        return $objectFieldList;
    }

    /***
     * ObjectField:
     *  : Name : Values
     */
    public function ObjectField()
    {
        $location = $this->tokenizer->getLastLocation();

        $name = $this->Name();
        $this->eat(":");
        $value = $this->Value();
        return [
            "kind" => "ObjectField",
            "name" => $name,
            "value" => $value,
            "loc" => $location
        ];
    }

    public function Name()
    {
        $location = $this->tokenizer->getLastLocation();
        return [
            "kind" => "Name",
            "value" => $this->eat("NAME")["value"],
            "loc" => $location
        ];
    }

    /***
     * Type
     *  : NamedType
     *  : ListType
     *  : NonNullType
     */
    public function Type()
    {
        $location = $this->tokenizer->getLastLocation();

        $type = null;
        $nonNullable = false;
        $isListType = false;

        if ($this->lookahead["type"] == "[") {
            $isListType = true;
            $this->eat("[");
            $type = $this->Type();
            $this->eat("]");
        } else {
            $type = [
                "kind" => "NamedType",
                "name" => $this->Name(),
                "loc" => $location
            ];
        }

        // check for list type
        if ($isListType) {
            $type = [
                "kind" => "ListType",
                "type" => $type,
                "loc" => $location
            ];
        }

        if ($this->lookahead["type"] == "!") {
            $this->eat("!");
            $nonNullable = true;
        }

        // check for non-nullable type
        if ($nonNullable) {
            $type = [
                "kind" => "NonNullType",
                "type" => $type,
                "loc" => $location
            ];
        }

        return $type;
    }


    /***
     * TypeCondition
     *  : on NamedType
     */
    public function TypeCondition()
    {
        $this->eat("ON");

        return $this->Type();
    }

    /***
     * NamedType
     *  : Name
     */
    public function NamedType()
    {
        return $this->Name();
    }

    /***
     * Arguments
     *  : ( ArgumentList )
     */
    public function Arguments()
    {
        $this->eat("(");
        $argumentList = $this->ArgumentList();
        $this->eat(")");
        return $argumentList;
    }

    /***
     * ArgumentList
     *  : Argument
     *  : ArgumentList
     */
    public function ArgumentList()
    {
        $argumentList = [$this->Argument()];

        while ($this->lookahead["type"] !== ")") {
            $this->eat(",");
            $argumentList[] = $this->Argument();
        }

        return $argumentList;
    }

    /***
     * Argument
     *  : Name : Values
     */
    public function Argument()
    {
        $location = $this->tokenizer->getLastLocation();

        $name = $this->Name();
        $this->eat(":");
        $value = $this->Value();
        return [
            "kind" => "Argument",
            "name" => $name,
            "value" => $value,
            "loc" => $location
        ];
    }

    /***
     * Directives
     *  : DirectiveList
     */
    public function Directives()
    {
        return $this->DirectiveList();
    }

    /***
     * DirectiveList
     *  : Directive
     *  : DirectiveList
     */
    public function DirectiveList()
    {
        $directiveList = [$this->Directive()];

        while ($this->lookahead["type"] === "@") {
            $directiveList[] = $this->Directive();
        }

        return $directiveList;
    }

    /***
     * Directive
     *  : @ Name Arguments?
     */
    public function Directive()
    {
        $location = $this->tokenizer->getLastLocation();

        $this->eat("@");

        $name = $this->Name();

        $arguments = [];
        if ($this->lookahead["type"] === "(") {
            $arguments = $this->Arguments();
        }
        return [
            "kind" => "Directive",
            "name" => $name,
            "arguments" => $arguments,
            "loc" => $location
        ];
    }

    /***
     * FragmentSpread
     *  : ... TypeCondition? Directives? SelectionSet
     */
    public function InlineFragment()
    {
        $location = $this->tokenizer->getLastLocation();

        $this->eat("...");

        $typeCondition = null;
        if ($this->lookahead["type"] === "ON") {
            $typeCondition = $this->TypeCondition();
        }

        $directives = [];
        if ($this->lookahead["type"] === "@") {
            $directives = $this->Directives();
        }

        $selectionSet = $this->SelectionSet();

        return [
            "kind" => "InlineFragment",
            "typeCondition" => $typeCondition,
            "directives" => $directives,
            "selectionSet" => $selectionSet,
            "loc" => $location
        ];
    }

    /***
     * FragmentSpread
     *  : ... FragmentName Directives?
     */
    public function FragmentSpread()
    {
        $location = $this->tokenizer->getLastLocation();

        $this->eat("...");

        $name = $this->FragmentName();

        $directives = [];
        if ($this->lookahead["type"] === "@") {
            $directives = $this->Directives();
        }

        return [
            "kind" => "FragmentSpread",
            "name" => $name,
            "directives" => $directives,
            "loc" => $location
        ];
    }

    /***
     * FragmentName
     *  : Name !!but not on!!
     */
    public function FragmentName()
    {
        return $this->Name();
    }

    /**
     * Eats the token by checking if it's type equals the given asserted type and updates the lookahead.
     *
     * @param $tokenType
     * @return mixed
     * @throws UnexpectedEndOfInputError
     * @throws UnexpectedTokenError
     */
    private function eat(string $tokenType)
    {
        $token = $this->lookahead;

        if ($token === null) {
            throw new UnexpectedEndOfInputError(
                "Unexpected end of input, expected \"$tokenType\"",
                $this->tokenizer->getLastLocation()
            );
        }
        if ($token["type"] !== $tokenType) {
            throw new UnexpectedTokenError(
                "Unexpected token: \"" . $token["value"] . "\", expected token of type \"$tokenType\", got tyoe \"" . $token["type"] . "\".",
                $this->tokenizer->getLastLocation()
            );
        }

        // advance to next token
        $this->lookahead = $this->tokenizer->getNextToken();

        return $token;
    }
}