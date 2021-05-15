<?php

namespace GraphQL\Errors;

use Throwable;

class GraphQLError extends \Exception
{
    protected $code;
    protected $node;
    protected $path;

    /**
     * GraphQLError constructor.
     * @param string $message
     * @param null $node
     * @param null $path
     */
    public function __construct($message = "", $node = null, $path = null)
    {
        parent::__construct($message);
        $this->node = $node;
        $this->path = array_filter(
            array_reverse($path ?? []),
            function ($pathItem) {
                return $pathItem !== null;
            }
        );
    }

    /**
     * Returns all given locations of errors in the query.
     *
     * @return null
     */
    public function getLocations()
    {
        return $this->node["loc"] ?? null;
    }

    /**
     * Returns all given locations of errors in the query.
     *
     * @return null
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Returns the internal code of the error.
     *
     * @return mixed
     */
    public function getErrorCode()
    {
        return $this->code;
    }
}

?>