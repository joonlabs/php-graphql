<p align="center">
    <img src="https://joonlabs.com/php-graphql/logo.svg" alt="index.js logo" width="300" align="center" style="width: 300px; display: block; margin-left: auto; margin-right: auto;"/>
</p>

# php-graphql

php-graphql is a pure php implementation of the latest GraphQL [specification](https://github.com/graphql/graphql-spec) based on the [reference implementation in JavaScript](https://github.com/graphql/graphql-js). 

## Installation
**git clone:**
````bash
git clone https://github.com/joonlabs/php-graphql.git
````
after downloading include the autloader.php, e.g.:
````php
require 'php-graphql/src/autoloader.php';
````
## Hello world!

```php
use GraphQL\Servers\Server;
use GraphQL\Schemas\Schema;
use GraphQL\Types\GraphQLString;
use GraphQL\Types\GraphQLObjectType;
use GraphQL\Fields\GraphQLTypeField;
use GraphQL\Arguments\GraphQLFieldArgument;

// build the query type
$QueryType = new GraphQLObjectType("Query", "Root Query", function (){
    return [
        new GraphQLTypeField(
            "hello",
            new GraphQLString(),
            "Your first hello world GraphQL-Application",
            function (){ return 'Hello world!'; }
        )
    ];
});

// build the schema
$schema = new Schema($QueryType);

// start a server
$server = new Server($schema);
$server->listen();
```

That's it! Now the GraphQL server is ready to accept requests at the URL of the PHP script.

## Backers and sponsors
<img src="https://joonlabs.com/php-graphql/backers/joon.png" alt="index.js logo" height="30"/><br>
see [joonlabs.com](https://joonlabs.com) 
<br>
<br>
<img src="https://joonlabs.com/php-graphql/backers/leafx.png" alt="index.js logo" height="30"/><br>
see [leafx.de](https://leafx.de)
<br>
<br>