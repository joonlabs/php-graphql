<?php

namespace GraphQL\Directives;

use GraphQL\Arguments\GraphQLDirectiveArgument;
use GraphQL\Types\GraphQLBoolean;
use GraphQL\Types\GraphQLNonNull;

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
            new GraphQLDirectiveArgument("if", new GraphQLNonNull(new GraphQLBoolean()), "Skipped when true.")
        ];

        $this->description = "Directs the executor to skip this field or fragment when the `if` argument is true.";

        $this->locations = [
            "FIELD",
            "FRAGMENT_SPREAD",
            "INLINE_FRAGMENT"
        ];
    }
}

