<?php

use PHPUnit\Framework\TestCase;
use GraphQL\Types\GraphQLInt;
use GraphQL\Errors\GraphQLError;

class GraphQLIntTest extends TestCase
{

    public function getType(): GraphQLInt
    {
        return new GraphQLInt();
    }

    /**
     * Allows us to test if input can be serialized by the type
     */
    public function testSerialize()
    {
        $type = $this->getType();

        self::assertSame(42, $type->serialize(42));

        self::expectException(GraphQLError::class);
        $type->serialize("42");
    }

    /**
     * Allows us to test if string input can be literalized by the type
     */
    public function testParseLiteral()
    {
        $type = $this->getType();

        $node = [
            "kind" => "IntValue",
            "value" => "42"
        ];

        self::assertSame(42, $type->parseLiteral($node, null));

        $node = [
            "kind" => "IntValue",
            "value" => "2147483648"
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

        self::assertSame(42, $type->parseValue(42));

        self::expectException(GraphQLError::class);
        $type->parseValue("42");
    }
}