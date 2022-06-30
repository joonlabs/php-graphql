<?php

use PHPUnit\Framework\TestCase;
use GraphQL\Errors\GraphQLError;

class ErrorTest extends TestCase
{

    /**
     * check if node can be converted into location correctly
     */
    public function testConvertNodeToPosition()
    {
        $node = [
            // ... more properties of node
            "loc" => [
                "line" => 1,
                "column" => 1,
            ]
        ];
        $Error = new GraphQLError("message", $node);
        self::assertSame($Error->getLocations(), [$node["loc"]]);
    }


}