<?php

use \PHPUnit\Framework\TestCase;
use \GraphQL\Types\GraphQLFloat;
use \GraphQL\Errors\GraphQLError;

class GraphQLFloatTest extends TestCase
{

    public function getType()
    {
        return new GraphQLFloat();
    }

    /**
     * Allows us to test if input can be serialized by the type
     */
    public function testSerialize()
    {
        $type = $this->getType();

        self::assertSame(42.24, $type->serialize(42.24));

        self::expectException(GraphQLError::class);
        $type->serialize("42.24");
    }

    /**
     * Allows us to test if string input can be literalized by the type
     */
    public function testParseLiteral()
    {
        $type = $this->getType();

        $node = [
            "kind" => "FloatValue",
            "value" => "42.24"
        ];

        self::assertSame(42.24, $type->parseLiteral($node, null));

        $node = [
            "kind" => "FloatValue",
            "value" => "hello world!"
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

        self::assertSame(42.24, $type->parseValue(42.24));

        self::expectException(GraphQLError::class);
        $type->parseValue("42.24");
    }
}