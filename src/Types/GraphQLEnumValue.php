<?php

namespace GraphQL\Types;

/**
 * Class GraphQLEnumValue
 * @package GraphQL\Types
 */
class GraphQLEnumValue
{

    private $id;
    private $description;
    private $deprecationReason;

    /**
     * GraphQLEnumValue constructor.
     * @param string $id
     * @param string $description
     * @param string|null $deprecationReason
     */
    public function __construct(string $id, string $description = "", ?string $deprecationReason = null)
    {
        $this->id = $id;
        $this->description = $description;
        $this->deprecationReason = $deprecationReason ?? null;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return ?string
     */
    public function getDeprecationReason(): ?string
    {
        return $this->deprecationReason;
    }
}