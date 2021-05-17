---
layout: default
title: Lists and Non-Nulls
parent: Types
nav_order: 4
---
# Lists and Non-Nulls
Lists and Non-Nulls are wrapping types in GraphQL. This means, that they cannot be used solely, but must feature an inner type, which they hold.


# Lists
**php-graphql** represents lists of other types as instances of the class `GraphQLList`, which accepts an inner type
as parameter in the constructor.

```php
use GraphQL\Types\GraphQLList;
use GraphQL\Types\GraphQLInt;

$ListOfInts = new GraphQLList(new GraphQLInt()); 
```

# Non-Null
**php-graphql** represents non-null types as instances of the class `GraphQLNonNull`, which accepts an inner type
as parameter in the constructor.

```php
use GraphQL\Types\GraphQLNonNull;
use GraphQL\Types\GraphQLInt;

$NonNullInt = new GraphQLNonNull(new GraphQLInt());
``` 