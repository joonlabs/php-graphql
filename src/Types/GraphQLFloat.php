<?php

namespace GraphQL\Types;

use GraphQL\Errors\GraphQLError;

class GraphQLFloat extends GraphQLScalarType
{
    protected $type = "GraphQLFloat";
    protected $description = "Default GraphQL Float Type";

    public function serialize($outputValue){
        if(!is_float($outputValue) and $outputValue!==null){
            throw new GraphQLError(
                "Value \"{$outputValue}\" is not of type \"{$this->getName()}\"."
            );
        }
        return $outputValue;
    }

    public function parseLiteral($valueNode, $variables)
    {
        if($valueNode["kind"] !== "IntValue" and $valueNode["kind"] !== "FloatValue"){
            throw new GraphQLError(
                "Float cannot represent non numeric value: {$valueNode["value"]}"
            );
        }

        return floatval($valueNode["value"]);
    }
}

?>