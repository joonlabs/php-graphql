<?php

namespace GraphQL\Types;

use GraphQL\Errors\BadUserInputError;
use GraphQL\Fields\GraphQLQueryField;
use GraphQL\Fields\GraphQLTypeField;

class GraphQLObjectType extends GraphQLType
{
    protected $type;
    protected $description;

    private $fields;
    private $interfaces;

    public function __construct(
        string $type,
        string $description,
        \Closure $fields,
        ?array $interfaces = null
    )
    {
        $this->type = $type;
        $this->description = $description;

        $this->fields = $fields;
        $this->interfaces = $interfaces ?? [];
    }

    /**
     * @return array
     */
    public function getInterfaces(): array
    {
        return $this->interfaces;
    }

    /**
     * @return array
     */
    public function getFields(): array
    {
        $allFields = call_user_func($this->fields);
        $fields = [];
        foreach ($allFields as $field) {
            $fields[$field->getName()] = $field;
        }
        return $fields;
    }
}

?>