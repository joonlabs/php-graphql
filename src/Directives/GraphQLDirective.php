<?php

namespace GraphQL\Directives;

class GraphQLDirective
{
    protected $name;
    protected $arguments;
    protected $description;
    protected $locations = [];
    protected $isRepetable = true;

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

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return mixed
     */
    public function getLocations()
    {
        return $this->locations;
    }

    /**
     * @return bool
     */
    public function isRepetable(): bool
    {
        return $this->isRepetable;
    }
}

?>