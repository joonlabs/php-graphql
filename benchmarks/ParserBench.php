<?php

use GraphQL\Errors\UnexpectedTokenError;
use GraphQL\Introspection\Introspection;
use GraphQL\Parser\Parser;

/**
 * @Warmup(3)
 * @Revs(10)
 * @Iterations(2)
 */
class ParserBench{

    /**
     * @throws UnexpectedTokenError
     */
    public function benchQueryIntrospection()
    {
        $query = Introspection::getIntrospectionQuery();

        $parser = new Parser();

        $parser->parse($query);
        $parser->getParsedDocument();
    }
}