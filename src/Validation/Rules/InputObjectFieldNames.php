<?php

namespace GraphQL\Validation\Rules;

use GraphQL\Errors\GraphQLError;
use GraphQL\Errors\ValidationError;
use GraphQL\Validation\ValidationContext;

class InputObjectFieldNames extends ValidationRule
{
    /**
     * Implements the rule specified under 5.6.2 (Input Object Field Names) in the GraphQL-Specs (version: 2018)
     * @param ValidationContext $validationContext
     * @return array
     */
    public function validate(ValidationContext $validationContext): void
    {
        // This rule is for performance reasons not implemented again, since Executor::execute
        // validates this when coercing variables (while building the ExecutionContext) via Ast::valueFromAst(...)
    }
}

?>