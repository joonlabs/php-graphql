<?php

namespace GraphQL\Types;

use GraphQL\Errors\GraphQLError;

class GraphQLString extends GraphQLScalarType
{
    protected $type = "GraphQLString";
    protected $description = "Default GraphQL String Type";

    public function serialize($outputValue){
        if(!is_string($outputValue) and $outputValue!==null){
            throw new GraphQLError(
                "Value \"{$outputValue}\" is not of type \"{$this->getName()}\"."
            );
        }
        return $outputValue;
    }

    public function parseLiteral($valueNode, $variables)
    {
        if($valueNode["kind"] !== "StringValue"){
            throw new GraphQLError(
                "String cannot represent a non string value: {$valueNode["value"]}"
            );
        }

        return $valueNode["value"];
    }
}

?>