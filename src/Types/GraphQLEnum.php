<?php

namespace GraphQL\Types;

use GraphQL\Errors\GraphQLError;
use GraphQL\Errors\ValidationError;

class GraphQLEnum extends GraphQLType
{
    protected $type = "Enum";
    protected $description = "Default GraphQL Enum Type";

    private $values;

    public function __construct(string $type, string $description, ?array $values)
    {
        $this->type = $type;
        $this->description = $description;
        $this->values = $values ?? [];
    }

    public function serialize($outputValue){
        if(!in_array($outputValue, $this->values)){
            throw new GraphQLError(
                "Value \"{$outputValue}\" does not exist in \"{$this->getName()}\" enum."
            );
        }

        return $outputValue;
    }

    public function parseLiteral($valueNode, $variables)
    {
        if($valueNode["kind"] !== "EnumValue"){
            throw new GraphQLError(
                "Enum cannot represent non enum value: {$valueNode["value"]}"
            );
        }

        $value = $valueNode["value"];
        if(!in_array($value, $this->values)){
            throw new GraphQLError(
                "Value \"{$value}\" does not exist in \"{$this->getName()}\" enum."
            );
        }
        return $value;
    }

    public function parseValue($value)
    {
        if(!is_string($value)){
            throw new GraphQLError(
                "Enum \"{$this->getName()}\" cannot represent non-string value: {$value}"
            );
        }

        if(!in_array($value, $this->values)){
            throw new GraphQLError(
                "Value \"{$value}\" does not exist in \"{$this->getName()}\" enum."
            );
        }

        return $value;
    }

    /**
     * @return array
     */
    public function getValues(): array
    {
        return $this->values;
    }
}

?>