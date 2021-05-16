# Installation
To install php-graphql, you basically have two options. You can either use composer or git submodules.

Composer (coming soon):
````bash
composer require joonlabs/php-graphql
````

Git-Submodule
````bash
git submodule add https://github.com/joonlabs/php-graphql.git
````
# Additional Tools
Although it is completely possible to communicate with the GraphQL API via HTTP requests, it is much more convenient to use graphical tools like [Altair](https://github.com/imolorhe/altair) or [GraphiQL](https://github.com/graphql/graphiql), while debugging and developing.
These tools offer syntax highlighting, autocompletion, documentation insights and much more.

# Hello world!

```php
use GraphQL\Servers\Server;
use GraphQL\Schemas\Schema;
use GraphQL\Types\GraphQLString;
use GraphQL\Types\GraphQLObjectType;
use GraphQL\Fields\GraphQLTypeField;

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

That's it! Now the GraphQL server is ready to accept requests at the URL of the PHP script. An example request can now look like this:
````graphql
query {
    hello
}
````
... and will return:

````json
{
  "data": {
    "hello" : "Hello world!"
  }
}
````

# What's next?
Since this example is extremely simple and does not really address what is possible with GraphQL, it is recommended to take a closer look at the [Star Wars](https://github.com/joonlabs/php-graphql/tree/master/examples/starwars) and [books](https://github.com/joonlabs/php-graphql/tree/master/examples/books) examples.  