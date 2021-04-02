<?php

namespace GraphQL\Errors;

use Throwable;

class GraphQLError extends \Exception
{
    protected $code;
    protected $locations;
    protected $path;

    /**
     * GraphQLError constructor.
     * @param string $message
     * @param null $location
     * @param null $path
     */
    public function __construct($message = "", $location=null, $path=null)
    {
        parent::__construct($message);
        $this->locations = [$location];
        $this->path = array_filter(
            array_reverse($path ?? []),
            function($pathItem){
                return $pathItem!==null;
            }
        );
    }

    /**
     * Returns all given locations of errors in the query.
     *
     * @return null
     */
    public function getLocations(){
        return $this->locations;
    }

    /**
     * Returns all given locations of errors in the query.
     *
     * @return null
     */
    public function getPath(){
        return $this->path;
    }

    /**
     * Returns the internal code of the error.
     *
     * @return mixed
     */
    public function getErrorCode(){
        return $this->code;
    }
}
?>