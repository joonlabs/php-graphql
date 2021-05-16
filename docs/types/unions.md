# Unions
A Union is an abstract type that holds other object types. When passed as type to a field, 
it means that the field can return any of the types held by the union. 

**php-graphql** represents unions as instances of the class `GraphQLUnion`, which accepts several parameters
in the constructor for configuring the union.

```php
use GraphQL\Types\GraphQLUnion;

$SearchResultType = new GraphQLUnion(
    "SearchResult", 
    "Type of data being returned by a search query", 
    [
        &$bookType, 
        &$authorType
    ]
);
```

# GraphQLUnion Constructor
Parameters used for the `GraphQLUnion` constructors are (in the following order):

| Parameter | Type | Description | Required |
| --- | --- | --- | --- |
| **type** | `string` | Name of the new union. Used for introspection. | ☑️ |
| **description** | `string` | Description of the new union. Used for introspection. | ☑️ |
| **types** | `array` | Array of `GraphQLObjectType` that holds all possible types of the new union. Used during validation and execution. | ☑️ |
| **resolveTypeFn** | `\Closure` | Function that decides to which type a data object belongs to. If not passed, the default resolver is used. Used during validation and execution. | ️ |
