<?php

namespace GraphQL\Types;

use GraphQL\Errors\GraphQLError;

class GraphQLBoolean extends GraphQLScalarType
{
    protected $type = "Boolean";
    protected $description = "Default GraphQL Boolean Type";

    public function serialize($outputValue){
        if(!is_bool($outputValue) and $outputValue!==null){
            throw new GraphQLError(
                "Value \"{$outputValue}\" is not of type \"{$this->getName()}\"."
            );
        }
        return $outputValue;
    }

    public function parseLiteral($valueNode, $variables)
    {
        if($valueNode["kind"] !== "BooleanValue"){
            throw new GraphQLError(
                "Boolean cannot represent non boolean value: {$valueNode["value"]}"
            );
        }

        return $valueNode["value"];
    }

    public function parseValue($value){
        if(!is_bool($value) and $value!==null){
            throw new GraphQLError(
                "Value \"{$value}\" is not of type \"{$this->getName()}\"."
            );
        }
        return $value;
    }
}

?>