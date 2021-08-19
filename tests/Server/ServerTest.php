<?php

use GraphQL\Errors\GraphQLError;
use PHPUnit\Framework\TestCase;
use GraphQL\Servers\Server;
use GraphQL\Schemas\Schema;
use GraphQL\Types\GraphQLString;
use GraphQL\Types\GraphQLObjectType;
use GraphQL\Fields\GraphQLTypeField;

class ServerTest extends TestCase
{

    /**
     * Allows us to check if comments are ignored
     * @throws GraphQLError
     */
    public function testCheckCommentsAreIgnored()
    {
        // build the query type
        $QueryType = new GraphQLObjectType("Query", "Root Query", function () {
            return [
                new GraphQLTypeField(
                    "hello",
                    new GraphQLString(),
                    "Your first hello world GraphQL-Application",
                    function () {
                        return 'Hello world!';
                    }
                )
            ];
        });

        // build the schema
        $schema = new Schema($QueryType);

        // start a server
        $server = new Server($schema);

        // get result
        $result = $server->handle("{hello}");

        $this->assertEquals(
            ["data" => ["hello" => "Hello world!"]],
            $result
        );
    }

}