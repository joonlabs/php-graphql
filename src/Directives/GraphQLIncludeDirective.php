<?php

namespace GraphQL\Directives;

use GraphQL\Arguments\GraphQLDirectiveArgument;
use GraphQL\Types\GraphQLBoolean;
use GraphQL\Types\GraphQLNonNull;

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
            new GraphQLDirectiveArgument("if", new GraphQLNonNull(new GraphQLBoolean()), "Included when true.")
        ];

        $this->description = "Directs the executor to include this field or fragment only when the `if` argument is true.";

        $this->locations = [
            "FIELD",
            "FRAGMENT_SPREAD",
            "INLINE_FRAGMENT"
        ];
    }
}

