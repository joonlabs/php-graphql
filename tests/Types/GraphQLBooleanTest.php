<?php

use PHPUnit\Framework\TestCase;
use GraphQL\Types\GraphQLBoolean;
use GraphQL\Errors\GraphQLError;

class GraphQLBooleanTest extends TestCase
{

    public function getType(): GraphQLBoolean
    {
        return new GraphQLBoolean();
    }

    /**
     * Allows us to test if input can be serialized by the type
     */
    public function testSerialize()
    {
        $type = $this->getType();

        self::assertSame(true, $type->serialize(true));

        self::expectException(GraphQLError::class);
        $type->serialize("true");
    }

    /**
     * Allows us to test if string input can be literalized by the type
     */
    public function testParseLiteral()
    {
        $type = $this->getType();

        $node = [
            "kind" => "BooleanValue",
            "value" => "true"
        ];

        self::assertSame("true", $type->parseLiteral($node, null));
    }


    /**
     * Allows us to test if value can be parsed
     */
    public function testParseValue()
    {
        $type = $this->getType();

        self::assertSame(false, $type->parseValue(false));

        self::expectException(GraphQLError::class);
        $type->parseValue("false");
    }
}