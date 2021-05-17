---
layout: default
title: Directives
parent: Types
nav_order: 7
---
# Directives
A directive describes how the data should be processed at a meta level. 
A directive can be attached to a field or fragment and can affect the execution of the query.

**php-graphql** implements the both directives given by the specification:
- **@include(if:Boolean)** A field or fragment is only included if this directive's parameter `if` is **true**  
- **@skip(if:Boolean)** A field or fragment is skipped if this directive's parameter `if` is **true**

For example:
```graphql
query Hero($episode: Episode, $withFriends: Boolean!) {
  hero(episode: $episode) {
    name
    friends @include(if: $withFriends) {
      name
    }
  }
}
```

The selection `friends` will only be included in the returning data, if the variable **$withFriends** is set to **true**.