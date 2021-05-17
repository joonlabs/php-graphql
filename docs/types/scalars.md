---
layout: default
title: Scalars
parent: Types
nav_order: 1
---
# Scalars
The GraphQL specification describes a hand full built-in types, that represent the leafs of a query or mutation and cannot be sub-selected in any way.
These types are called scalars:

```php
use GraphQL\Types\GraphQLString;
use GraphQL\Types\GraphQLInt;
use GraphQL\Types\GraphQLFloat;
use GraphQL\Types\GraphQLBoolean;
use GraphQL\Types\GraphQLID;

$StringType  = new GraphQLString();  // utf-8 string
$IntType     = new GraphQLInt();     // 32-bit integer
$FloatType   = new GraphQLFloat();   // float
$BooleanType = new GraphQLBoolean(); // boolean
$IDType      = new GraphQLID();      // id (parsed and handled as string)
```

# Custom Scalars
You can write your own scalar types, by extending the GraphQLScalar class:

```php
<?php

namespace MyProject;

use GraphQL\Errors\GraphQLError;
use GraphQL\Types\GraphQLScalarType;

class MyCustomStringType extends GraphQLScalarType
{
    protected $type = "CustomStringType";
    protected $description = "Custom String Type";

    /**
     * Serializes an internal value to include in a response.
     * 
     * @param $outputValue
     * @return string|null
     * @throws GraphQLError
     */
    public function serialize($outputValue)
    {
        if (!is_string($outputValue) and $outputValue !== null) {
            throw new GraphQLError(
                "Value \"{$outputValue}\" is not of type \"{$this->getName()}\"."
            );
        }
        return $outputValue;
    }

    /**
     * Parses an externally provided value (query variable) to use as an input
     * 
     * @param $valueNode
     * @param $variables
     * @return mixed
     * @throws GraphQLError
     */
    public function parseLiteral($valueNode, $variables)
    {
        if ($valueNode["kind"] !== "StringValue") {
            throw new GraphQLError(
                "String cannot represent a non string value: {$valueNode["value"]}"
            );
        }

        return $valueNode["value"];
    }

    /**
     * Parses an externally provided literal value (hardcoded in GraphQL query) to use as an input.
     * 
     * @param $value
     * @return string|null
     * @throws GraphQLError
     */
    public function parseValue($value)
    {
        if (!is_string($value) and $value !== null) {
            throw new GraphQLError(
                "Value \"{$value}\" is not of type \"{$this->getName()}\"."
            );
        }
        return $value;
    }
}

?>
```

