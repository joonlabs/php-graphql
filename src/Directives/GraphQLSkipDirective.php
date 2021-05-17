<?php

namespace GraphQL\Directives;

use GraphQL\Arguments\GraphQLDirectiveArgument;
use GraphQL\Types\GraphQLBoolean;

/**
 * Class GraphQLSkipDirective
 * @package GraphQL\Directives
 */
class GraphQLSkipDirective extends GraphQLDirective
{
    protected $name = "skip";

    /**
     * GraphQLSkipDirective constructor.
     */
    public function __construct()
    {
        $this->arguments = [
            new GraphQLDirectiveArgument("if", new GraphQLBoolean(), "Determines whether to skip the target field or not", false)
        ];
    }
}

