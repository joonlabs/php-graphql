<?php
// include the php-graphql-autoloader
require '../../src/autoloader.php';

use GraphQL\Types\GraphQLObjectType;
use GraphQL\Fields\GraphQLTypeField;
use GraphQL\Types\GraphQLString;
use GraphQL\Schemas\Schema;
use GraphQL\Servers\Server;

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