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
    private $isTypeOfFn;

    public function __construct(string $type, string $description, \Closure $fields, ?array $interfaces = null, ?\Closure $isTypeOfFn = null)
    {
        $this->type = $type;
        $this->description = $description;

        $this->fields = $fields;
        $this->interfaces = $interfaces ?? [];
        $this->isTypeOfFn = $isTypeOfFn ?? function ($value, $contextValue, $info) {
                // if $value is array, check if all keys match
                if (is_array($value)) {
                    $valueKeys = array_keys($value);
                    $fieldKeys = array_keys($this->getFields());
                    $countCommonKeys = count(array_intersect($valueKeys, $fieldKeys));
                    if ($countCommonKeys === count($valueKeys)) {
                        return true;
                    }
                    return false;
                }

                // if $value is object, check if getters for all keys exist
                if (is_object($value)) {
                    $fieldKeys = array_keys($this->getFields());
                    $canAcessAllGettersOrVariables = true;
                    foreach ($fieldKeys as $fieldKey) {

                        if (!method_exists($value, "get" . ucwords($fieldKey))
                            and !property_exists($value, $fieldKey)) {
                            $canAcessAllGettersOrVariables = false;
                        }
                    }
                    return $canAcessAllGettersOrVariables;
                }

                return false;
            };

        return $this;
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

    /**
     * @param $value
     * @param $contextValue
     * @param $info
     * @return false|mixed
     */
    public function isTypeOf($value, $contextValue, $info)
    {
        return call_user_func($this->isTypeOfFn, $value, $contextValue, $info);
    }

    /**
     * @param string $type
     * @return GraphQLObjectType
     */
    public function setType(string $type): GraphQLObjectType
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @param string $description
     * @return GraphQLObjectType
     */
    public function setDescription(string $description): GraphQLObjectType
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @param \Closure $fields
     * @return GraphQLObjectType
     */
    public function setFields(\Closure $fields): GraphQLObjectType
    {
        $this->fields = $fields;
        return $this;
    }

    /**
     * @param array $interfaces
     * @return GraphQLObjectType
     */
    public function setInterfaces(array $interfaces): GraphQLObjectType
    {
        $this->interfaces = $interfaces;
        return $this;
    }

    /**
     * @param \Closure $isTypeOfFn
     * @return GraphQLObjectType
     */
    public function setIsTypeOfFn(\Closure $isTypeOfFn): GraphQLObjectType
    {
        $this->isTypeOfFn = $isTypeOfFn;
        return $this;
    }
}

?>