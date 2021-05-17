---
layout: default
title: Interfaces
parent: Types
nav_order: 5
---
# Interfaces
An Interface is an abstract type that includes a certain set of fields that a type must include to implement the interface.
An Interface can never be returned directly but must be implemented by 'child types'.

**php-graphql** represents interfaces as instances of the class `GraphQLInterface`, which accepts several parameters
in the constructor for configuring the interface.

```php
use GraphQL\Types\GraphQLInterface;
use GraphQL\Fields\GraphQLTypeField;
use GraphQL\Types\GraphQLNonNull;
use GraphQL\Types\GraphQLString;
use GraphQL\Types\GraphQLList;

$Character = new GraphQLInterface("Character", "A character in the Star Wars Trilogy.", function () use (&$Character, &$Episode) {
    return [
        new GraphQLTypeField("id", new GraphQLNonNull(new GraphQLString()), "The id of the character."),
        new GraphQLTypeField("name", new GraphQLString(), "The name of the character."),
        new GraphQLTypeField("friends", new GraphQLList($Character), "The friends of the character, or an empty list if they have none."),
        new GraphQLTypeField("appearsIn", new GraphQLList($Episode), "Which movies they appear in."),
    ];
}, function ($character) {
    if ($character["type"] === "human") {
        return "Human";
    }
    return "Droid";
});
```
# GraphQLInterface Constructor
Parameters used for the `GraphQLInterface` constructors are (in the following order):

| Parameter | Type | Description | Required |
| --- | --- | --- | --- |
| **type** | `string` | Name of the new interface. Used for introspection. | ☑️ |
| **description** | `string` | Description of the new interface. Used for introspection. | ☑️ |
| **fields** | `\Closure` | Function that returns an array of `GraphQLTypeField`. Each field must be implemented in the type that implements this interface. Is called as soon the fields are needed. Used during validation and execution. | ☑️ |
| **resolveTypeFn** | `\Closure` | Function that decides to which implementing object type a data object belongs to. If not passed, the default resolver is used. Used during validation and execution. | ️ |


# Implementing Interfaces
Interfaces can be implemented by a `GraphQLObjectType`, when passing the interface (reference) to the `GraphQLObjectType` constructor (see parameters of `GraphQLObjectType` constructor in the according section).  