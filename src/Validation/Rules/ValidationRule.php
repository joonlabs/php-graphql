<?php

namespace GraphQL\Validation\Rules;

use GraphQL\Errors\GraphQLError;
use GraphQL\Validation\ValidationContext;

abstract class ValidationRule
{
    private $errors = [];

    /**
     * Runs the rule's logic against a ValidationContext-object. Adds all occuring error to
     * the internal error list.
     * @param ValidationContext $validationContext
     * @return void
     */
    abstract public function validate(ValidationContext $validationContext) : void;

    /**
     * Appends an GraphQLError to the internal errors list
     * @param GraphQLError $error
     */
    protected function addError(GraphQLError $error)
    {
        $this->errors[] = $error;
    }

    /**
     * Returns wether the rule was validated or not
     * @return bool
     */
    public function violated():bool
    {
        return count($this->errors)>0;
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }


}

?>