---
layout: default
title: Enums
parent: Types
nav_order: 3
---

# Enums
Enumeration types are a special kind of scalar that is restricted to a particular set of allowed values and in this implementation handled as string values.

**php-graphql** represents enums as instances of the class `GraphQLEnum`, which accepts a name, description and possible values
(`GraphQlEnumValue`) as parameters in the constructor.

```php
use GraphQL\Types\GraphQLEnum;
use GraphQL\Types\GraphQLEnumValue;

$Episode = new GraphQLEnum("Episode", "One of the films in the Star Wars Trilogy.", [
    new GraphQLEnumValue("NEW_HOPE", "Released in 1977."),
    new GraphQLEnumValue("EMPIRE", "Released in 1980."),
    new GraphQLEnumValue("JEDI", "Released in 1983.")
]);
```