<?php

use \PHPUnit\Framework\TestCase;
use \GraphQL\Types\GraphQLEnum;
use \GraphQL\Types\GraphQLEnumValue;
use \GraphQL\Errors\GraphQLError;

class GraphQLEnumTest extends TestCase
{

    public function getType()
    {
        return new GraphQLEnum("enum", "", [
            new GraphQLEnumValue("VAL1", "Value #1."),
            new GraphQLEnumValue("VAL2", "Value #2."),
        ]);
    }

    /**
     * Allows us to test if input can be serialized by the type
     */
    public function testSerialize()
    {
        $type = $this->getType();

        self::assertSame("VAL1", $type->serialize("VAL1"));

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
            "kind" => "EnumValue",
            "value" => "VAL1"
        ];

        self::assertSame("VAL1", $type->parseLiteral($node, null));

        $node = [
            "kind" => "EnumValue",
            "value" => "223245"
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

        self::assertSame("VAL2", $type->parseValue("VAL2"));

        self::expectException(GraphQLError::class);
        $type->parseValue(42);
    }
}