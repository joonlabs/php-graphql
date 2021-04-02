<?php
namespace GraphQL\Directives;

use GraphQL\Arguments\GraphQLDirectiveArgument;
use GraphQL\Types\GraphQLBoolean;

class GraphQLIncludeDirective extends GraphQLDirective {
    protected $name = "include";

    public function __construct()
    {
        $this->arguments = [
            new GraphQLDirectiveArgument("if", new GraphQLBoolean(), true)
        ];
    }
}
?>