# Server
The default `Server` is a simple wrapper around a parser, validator and executor to save you work. 
When constructing a server, simply pass the schema to the constructor and enable processing via the `listen()` function.

```php
use GraphQL\Servers\Server;

// create server and listen for inputs
$server = new Server($schema);
$server->listen();
```

For more details on parsing, validation and execution, take a look at the according section in the docs.