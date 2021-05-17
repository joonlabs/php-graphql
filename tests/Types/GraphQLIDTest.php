<?php

use PHPUnit\Framework\TestCase;
use GraphQL\Types\GraphQLID;
use GraphQL\Errors\GraphQLError;

class GraphQLIDTest extends TestCase
{

    public function getType(): GraphQLID
    {
        return new GraphQLID();
    }

    /**
     * Allows us to test if input can be serialized by the type
     */
    public function testSerialize()
    {
        $type = $this->getType();

        self::assertSame("AZ91", $type->serialize("AZ91"));

        self::expectException(GraphQLError::class);
        $type->serialize(22);
    }

    /**
     * Allows us to test if string input can be literalized by the type
     */
    public function testParseLiteral()
    {
        $type = $this->getType();

        $node = [
            "kind" => "StringValue",
            "value" => "AZ91"
        ];

        self::assertSame("AZ91", $type->parseLiteral($node, null));

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

        self::assertSame("AZ91", $type->parseValue("AZ91"));

        self::expectException(GraphQLError::class);
        $type->parseValue(42);
    }
}