---
layout: default
title: Types
nav_order: 4
has_children: true
permalink: /docs/types/
---

# Types
In **php-graphql**, types are represented as instances of a GraphQL-Type-Class. Supported built in types are:
- ```GraphQLBoolean()```
- ```GraphQLEnum()```
- ```GraphQLFloat()```
- ```GraphQLID()```
- ```GraphQLInputObjectType()```
- ```GraphQLInt()```
- ```GraphQLInterface()```
- ```GraphQLList()```
- ```GraphQLNonNull()```
- ```GraphQLObjectType()```
- ```GraphQLString()```
- ```GraphQLUnion()```

# Definition
All types are created inline. This means that to give an object a type, you just need to create an instance of that type. For example:
```php
use GraphQL\Types\GraphQLObjectType;
use GraphQL\Fields\GraphQLTypeField;
use GraphQL\Types\GraphQLInt;
use GraphQL\Types\GraphQLEnum;
use GraphQL\Types\GraphQLEnumValue;
use GraphQL\Types\GraphQLString;

// create enum type
$MyEnumType = new GraphQLEnum(
    "MyEnumType",
    "This is the description of my enum type",
    [
        new GraphQLEnumValue("PossibleValue1"),
        new GraphQLEnumValue("PossibleValue2"),
        new GraphQLEnumValue("PossibleValue3")
    ]
);

// create object type
$MyObjectType = new GraphQLObjectType(
    "MyObjectType",
    "This is the description of my object type",
    function() use(&$MyEnumType){
        return [
            new GraphQLTypeField("myIntField", new GraphQLInt()),
            new GraphQLTypeField("myStringField", new GraphQLString()),
            new GraphQLTypeField("myEnumField", $MyEnumType)
        ];   
    }
);
```

For more details about each type, just check the corresponding sections.