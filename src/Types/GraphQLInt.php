<?php

namespace GraphQL\Types;

use GraphQL\Errors\GraphQLError;

class GraphQLInt extends GraphQLScalarType
{
    protected $type = "GraphQLInt";
    protected $description = "Default GraphQL Integer Type";

    public function serialize($outputValue){
        if(!is_int($outputValue) and $outputValue!==null){
            throw new GraphQLError(
                "Value \"{$outputValue}\" is not of type \"{$this->getName()}\"."
            );
        }
        return $outputValue;
    }

    public function parseLiteral($valueNode, $variables)
    {
        if($valueNode["kind"] !== "IntValue"){
            throw new GraphQLError(
                "Int cannot represent non-integer value: {$valueNode["value"]}"
            );
        }

        $num = intval($valueNode["value"]);
        // TODO: check for 32-BIT integer (see: https://github.com/graphql/graphql-js/blob/5ed55b89d526c637eeb9c440715367eec8a2adec/src/type/scalars.js#L74)
        return $num;
    }
}

?>