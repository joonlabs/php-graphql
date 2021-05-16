# Introduction
This section describes how parsing, validation and execution in a high level and should only be interesting for building an own processing pipeline or understanding how the GraphQL-Server works. 
In all other cases the default `Server` should be fine to use.

# Parsing
Before a query can be executed, it must first be parsed into a so-called document. The string of a query is converted into an associative array, which contains more detailed information about certain parts of the query, such as locations of different words, their meaning in the current context, and so on. This parsed document can then be passed to a validator, or directly to an executor.

Parsing can be done like this:
```php
use GraphQL\Parser\Parser;

// define the query
$query = "
    query {
        helloWorld
    }";

// create parser and parse the query
$parser = new Parser();
$parser->parse($query);

// check if query was syntactically correct and obtain parsed document
if($parser->queryIsValid()){
    $document = $parser->getParsedDocument();
}
```

# Validation
A validator takes a parsed document, and a schema to decide wether the given operations in the document are valid against the schema or not.

Validation can be done like this:

```php
use GraphQL\Validation\Validator;

// create validator and validate document against schema
$validator = new Validator();
$validator->validate($schema, $document);

// check if document was valid against the schema
if($validator->documentIsValid()){
    // continue with execution
}
```

# Execution
An exeutor takes a parsed document, and a schema to perform all operation definitions given by the document on the schema. 
This can be any kind of query or mutation, or multiple of those. It is highly recommended to validate the query first and execute it afterwards to prevent errors during execution. 
If you are using the default `Server` this work is already done for you.

```php
use GraphQL\Execution\Executor;

// create executor 
$executor = new Executor();

// execute document
$result = $executor->execute($schema, $document);
```