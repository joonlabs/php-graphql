<?php

namespace GraphQL\Directives;

use GraphQL\Arguments\GraphQLDirectiveArgument;
use GraphQL\Types\GraphQLBoolean;

/**
 * Class GraphQLIncludeDirective
 * @package GraphQL\Directives
 */
class GraphQLIncludeDirective extends GraphQLDirective
{
    protected $name = "include";

    /**
     * GraphQLIncludeDirective constructor.
     */
    public function __construct()
    {
        $this->arguments = [
            new GraphQLDirectiveArgument("if", new GraphQLBoolean(), "Determines whether to include the target field or not", true)
        ];
    }
}

