<?php

namespace GraphQL\Execution;

use GraphQL\Errors\GraphQLError;
use GraphQL\Schemas\Schema;

class ExecutionContext
{
    private $schema;
    private $fragments;
    private $rootValue;
    private $contextValue;
    private $operation;
    private $variableValues;
    private $fieldResolver;
    private $typeResolver;
    private $errors;

    public function __construct(
        Schema $schema,
        array $fragments,
        $rootValue,
        array $contextValue,
        $operation,
        $variableValues,
        $fieldResolver,
        $typeResolver,
        $errors
    )
    {
        $this->schema = $schema;
        $this->fragments = $fragments;
        $this->rootValue = $rootValue;
        $this->contextValue = $contextValue;
        $this->operation = $operation;
        $this->variableValues = $variableValues;
        $this->fieldResolver = $fieldResolver;
        $this->typeResolver = $typeResolver;
        $this->errors = $errors;
    }

    /**
     * Appends an error to the internal error-list.
     * @param GraphQLError $error
     */
    public function addError(GraphQLError $error)
    {
        $this->errors[] = $error;
    }

    /**
     * @return Schema
     */
    public function getSchema(): Schema
    {
        return $this->schema;
    }

    /**
     * @param Schema $schema
     * @return ExecutionContext
     */
    public function setSchema(Schema $schema): ExecutionContext
    {
        $this->schema = $schema;
        return $this;
    }

    /**
     * @return array
     */
    public function getFragments(): array
    {
        return $this->fragments;
    }

    /**
     * @param array $fragments
     * @return ExecutionContext
     */
    public function setFragments(array $fragments): ExecutionContext
    {
        $this->fragments = $fragments;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRootValue()
    {
        return $this->rootValue;
    }

    /**
     * @param mixed $rootValue
     * @return ExecutionContext
     */
    public function setRootValue($rootValue): ExecutionContext
    {
        $this->rootValue = $rootValue;
        return $this;
    }

    /**
     * @return mixed
     */
    public function &getContextValue()
    {
        return $this->contextValue;
    }

    /**
     * @param mixed $contextValue
     * @return ExecutionContext
     */
    public function setContextValue($contextValue): ExecutionContext
    {
        $this->contextValue = $contextValue;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * @param mixed $operation
     * @return ExecutionContext
     */
    public function setOperation($operation): ExecutionContext
    {
        $this->operation = $operation;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getVariableValues()
    {
        return $this->variableValues;
    }

    /**
     * @param mixed $variableValues
     * @return ExecutionContext
     */
    public function setVariableValues($variableValues): ExecutionContext
    {
        $this->variableValues = $variableValues;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getFieldResolver()
    {
        return $this->fieldResolver;
    }

    /**
     * @param mixed $fieldResolver
     * @return ExecutionContext
     */
    public function setFieldResolver($fieldResolver): ExecutionContext
    {
        $this->fieldResolver = $fieldResolver;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param mixed $errors
     * @return ExecutionContext
     */
    public function setErrors($errors): ExecutionContext
    {
        $this->errors = $errors;
        return $this;
    }

    /**
     * @param GraphQLError $error
     * @return ExecutionContext
     */
    public function pushError(GraphQLError $error): ExecutionContext
    {
        $this->errors[] = $error;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTypeResolver()
    {
        return $this->typeResolver;
    }

    /**
     * @param mixed $typeResolver
     * @return ExecutionContext
     */
    public function setTypeResolver($typeResolver): ExecutionContext
    {
        $this->typeResolver = $typeResolver;
        return $this;
    }
}

