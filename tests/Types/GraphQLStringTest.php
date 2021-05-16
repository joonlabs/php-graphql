<?php

use \PHPUnit\Framework\TestCase;
use \GraphQL\Types\GraphQLString;
use \GraphQL\Errors\GraphQLError;

class GraphQLStringTest extends TestCase
{

    public function getType()
    {
        return new GraphQLString();
    }

    /**
     * Allows us to test if input can be serialized by the type
     */
    public function testSerialize()
    {
        $type = $this->getType();

        self::assertSame("Hello World!", $type->serialize("Hello World!"));

        self::expectException(GraphQLError::class);
        $type->serialize(42);
    }

    /**
     * Allows us to test if string input can be literalized by the type
     */
    public function testParseLiteral()
    {
        $type = $this->getType();

        $node = [
            "kind" => "StringValue",
            "value" => "Hello World!"
        ];

        self::assertSame("Hello World!", $type->parseLiteral($node, null));

        $node = [
            "kind" => "IntValue",
            "value" => "42"
        ];

        self::expectException(GraphQLError::class);
        $type->parseLiteral($node, null);
    }


    /**
     * Allows us to test if value can be parsed
     */
    public function testParseValue()
    {
        $type = $this->getType();

        self::assertSame("Hello World!", $type->parseValue("Hello World!"));

        self::expectException(GraphQLError::class);
        $type->parseValue(42);
    }
}