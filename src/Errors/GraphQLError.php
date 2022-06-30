<?php

namespace GraphQL\Errors;

use Exception;

/**
 * Class GraphQLError
 * @package GraphQL\Errors
 */
class GraphQLError extends Exception
{
    protected $code;
    protected $node;
    protected $path;

    protected $customExtensions;

    /**
     * GraphQLError constructor.
     * @param string $message
     * @param array|null $node
     * @param array|null $path
     * @param array|null $customExtensions
     */
    public function __construct(string $message = "", array $node = null, array $path = null, array $customExtensions=null)
    {
        parent::__construct($message);
        $this->node = $node;
        $this->path = $path;
        $this->customExtensions = $customExtensions;
    }

    /**
     * Returns all given locations of errors in the query.
     *
     * @return array|null
     */
    public function getLocations(): ?array
    {
        return [$this->node["loc"]] ?? null;
    }

    /**
     * Returns all given locations of errors in the query.
     *
     * @return array|null
     */
    public function getPath(): ?array
    {
        return $this->path;
    }

    /**
     * Returns all given locations of errors in the query.
     *
     * @return array|null
     */
    public function getSimplifiedPath(): ?array
    {
        $simplifiedPath = [];
        $currentPathField = $this->path;
        while($currentPathField !== null){
            $simplifiedPath[] = $currentPathField[0];
            $currentPathField = $currentPathField[2];
        }
        return array_reverse($simplifiedPath);
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

    /**
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function addCustomExtension(string $key, $value): GraphQLError
    {
        $this->customExtensions[$key] = $value;
        return $this;
    }

    /**
     * Returns all added custom extensions
     * @return array
     */
    public function getCustomExtensions(): array
    {
        return $this->customExtensions ?? [];
    }

    /**
     * Returns all extensions of the error.
     *
     * @return array
     */
    public function getExtensions(): array
    {
        return array_merge([
            "code" => $this->getErrorCode()
        ], $this->getCustomExtensions());
    }
}

