---
layout: default
title: Introduction
nav_order: 1
has_children: false
permalink: /docs/
---
# Introduction
GraphQL is a query language for APIs and a runtime for fulfilling those queries with your existing data. GraphQL provides a complete and understandable description of the data in your API, gives clients the power to ask for exactly what they need and nothing more, makes it easier to evolve APIs over time, and enables powerful developer tools.

At its core, GraphQL is a standard (see the [official specification](https://spec.graphql.org)) developed by Facebook Engineers. This means that GraphQL is implementation independent.
A well-documented overview of the features and capabilities of GraphQL can be found on the official website, all of which were implemented in this project.

# php-graphql

**php-graphql** is a new and feature-complete implementation of the GraphQL specifications, based on the [refernce implenentation in JavaScript](https://github.com/graphql/graphql-js).
The goal of this project is to create a lightweight and superfast alternative to other PHP GraphQL implementations, which also supports file upload from scratch.

To achieve this goal, the library features its own LL(1) parser, validator and executor. Also, performance critical duplicate validations by validator and executor are tried to be avoided to allow fast validation and subsequent execution.

The following features are included in the library:
- GraphQL-Types for building a Type System
- Introspection of your Type System (for tools like [Altair](https://github.com/imolorhe/altair) or [GraphiQL](https://github.com/graphql/graphiql))
- Parsing, validating and executing GraphQL queries
- Automated input handling and easy GraphQL-Server setup

# Status
The first release of this project was published in May 2021. 
The current relaese supports all features described by the official specification.
As this project was developed out of internal needs to overcome shortcomings of other implementations, it will be maintained for several years. 

# Source Code
The project's source code is hosted on [GitHub](https://github.com/joonlabs/php-graphql/)