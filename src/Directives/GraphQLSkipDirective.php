<?php
namespace GraphQL\Directives;

use GraphQL\Arguments\GraphQLDirectiveArgument;
use GraphQL\Types\GraphQLBoolean;

class GraphQLSkipDirective extends GraphQLDirective {
    protected $name = "skip";

    public function __construct()
    {
        $this->arguments = [
            new GraphQLDirectiveArgument("if", new GraphQLBoolean(), false)
        ];
    }
}
?>