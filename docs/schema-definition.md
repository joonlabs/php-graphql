---
layout: default
title: Schema Definition
nav_order: 3
has_children: false
permalink: /docs/schema-definition/
---
# Schema Definition
The schema is a container for your type hierarchy that accepts object types as roots for the query type, and the mutation type. 
It is passed to the validator and the executor and used to model your underlying data structures.

````php
use GraphQL\Servers\Server;

$schema = new Schema(
    $QueryType,
    $MutationType
);
````

# Query and Mutation
As described above, the schema consits of two root types (which are both object types):
- the **query** type for performing read operations on your data and
- the **mutation** type for performing changes in and operations on your data (this is completely optional)

```php
use GraphQL\Types\GraphQLInt;
use GraphQL\Types\GraphQLString;
use GraphQL\Types\GraphQLNonNull;
use GraphQL\Types\GraphQLObjectType;
use GraphQL\Fields\GraphQLTypeField;
use GraphQL\Arguments\GraphQLFieldArgument;

$QueryType = new GraphQLObjectType("Query", "Root Query", function (){
    return [
        new GraphQLTypeField(
            "hello",
            new GraphQLString(),
            "Your first hello world GraphQL-Application's query",
            function (){ return 'Hello world!'; }
        )
    ];
});

$MutationType = new GraphQLObjectType("Mutation", "Root Mutation", function (){
    return [
        new GraphQLTypeField(
            "multiply",
            new GraphQLInt(),
            "Your first hello world GraphQL-Application's mutation",
            function ($parent, $args, $context, $info){ 
                return $args["a"] * $args["b"]; 
            },
            [
                new GraphQLFieldArgument("a", new GraphQLNonNull(new GraphQLInt())),
                new GraphQLFieldArgument("b", new GraphQLNonNull(new GraphQLInt())),
            ]
        )
    ];
});
```