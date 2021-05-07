<?php

namespace GraphQL\Validation;

use GraphQL\Schemas\Schema;

class ValidationContext
{
    private $schema;
    private $document;

    public function __construct(Schema $schema, array $document)
    {
        $this->schema = $schema;
        $this->document = $document;
    }

    /**
     * @return Schema
     */
    public function getSchema(): Schema
    {
        return $this->schema;
    }

    /**
     * @return array
     */
    public function getDocument(): array
    {
        return $this->document;
    }
}

?>