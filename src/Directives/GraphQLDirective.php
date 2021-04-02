<?php
namespace GraphQL\Directives;

class GraphQLDirective{
    protected $name;
    protected $arguments;

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getArguments()
    {
        return $this->arguments;
    }
}
?>