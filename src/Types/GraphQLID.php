<?php

namespace GraphQL\Types;

use GraphQL\Errors\GraphQLError;

class GraphQLID extends GraphQLScalarType
{
    protected $type = "ID";
    protected $description = "Default GraphQL ID Type";

    public function serialize($outputValue){
        if(!is_int($outputValue) and !is_string($outputValue) and $outputValue!==null){
            throw new GraphQLError(
                "Value \"{$outputValue}\" is not of type \"{$this->getName()}\"."
            );
        }
        return strval($outputValue);
    }

    public function parseLiteral($valueNode, $variables)
    {
        if($valueNode["kind"] !== "StringValue" and $valueNode["kind"] !== "IntValue"){
            throw new GraphQLError(
                "ID cannot represent a non-string and non-integer value: {$valueNode["value"]}"
            );
        }

        return $valueNode["value"];
    }
}

?>