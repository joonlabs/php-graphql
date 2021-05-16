# Object Type
Object Types represent the probably most used primitive types in GraphQL applications. The hold a set of fields, 
where each field has it's own type, which lays the foundation for building complex hierachies and systems.

In **php-graphql** object types are an instance of the `GraphQLObjectType` class, which are configured via the constructor.

```php
/*
 * HINT: this code section is an excerpt of the books example in the 'examples/' folder
 *       and is not ment to work solely.
 */
$bookType = new GraphQLObjectType("Book", "The Book Type", function () use (&$authorType, &$bookCategoryType, &$authors, &$bookType, &$books) {
    return [
        new GraphQLTypeField(
            "id",
            new GraphQLNonNull(new GraphQLInt()),
            "ID of the Book"
        ),
        new GraphQLTypeField(
            "name",
            new GraphQLString(),
            "Name of the Book",
            function ($parent, $args, &$context) {
                return ($args["prefix"] ?? "") . $parent["name"] . ($context["addOn"] ?? "");
            },
            [
                new GraphQLTypeFieldArgument("prefix", new GraphQLString())
            ]
        ),
        new GraphQLTypeField(
            "author",
            new GraphQLNonNull($authorType),
            "Author of the book",
            function ($parent, $args) use ($authors) {
                $index = array_search($parent["authorId"], array_column($authors, 'id'));
                return $authors[$index];
            }
        )
    ];
});
```

# GraphQLObjectType Constructor
Parameters used for the `GraphQLObjectType` constructors are (in the following order):


| Parameter | Type | Description | Required |
| --- | --- | --- | --- |
| **type** | `string` | Name of the new type. Used for introspection. | ☑️ |
| **description** | `string` | Description of the new type. Used for introspection. | ☑️ |
| **fields** | `\Closure` | Function that returns an array of `GraphQLTypeField`. Is called as soon the fields are needed. Used during validation and execution. | ☑️ |
| **interfaces** | `array` | Array of `GraphQLInterface` that should be implemented. |  |
| **isTypeOfFn** | `\Closure` | Function that returns wether an object or array is type of this object or not. Used during execution. If not passed, uses default resolver. |  |

# GraphQLTypeField Constructor
A `GraphQLTypeField` is a field that can only be used in a field set e.g. in a `GraphQLObjectType`. 
Parameters used for the `GraphQLTypeField` constructors are (in the following order):

| Parameter | Type | Description | Required |
| --- | --- | --- | --- |
| **id** | `string` | Name of the new field. Used for introspection, validation and execution. | ☑️ |
| **type** | `GraphQLType` | The type of the new field. | ☑️ |
| **description** | `string` | Description of the new field. Used for introspection. | ☑️ |
| **resolve** | `\Closure` | Function whose signature looks like this ```function($parent, $args, $context, $info) {...}```. It should resolve the field's actual content based on the parameters. If not passed, uses default resolver. | ️ |
| **args** | `array` | Array of `GraphQLFieldArgument` that represent possible input arguments. |  |
| **defaultValue** | `mixed` | The field's default value (deprected). |  |
| **deprecationReason** | `string` | If passed, the field is marked internally as deprected and the argument is stored as explanation. |  |
