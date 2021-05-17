<?php

namespace GraphQL\Validation\Rules;

use GraphQL\Validation\ValidationContext;

class AllVariableUsagesAreAllowed extends ValidationRule
{
    /**
     * Implements the rule specified under 5.8.5 (All Variable Usages Are Allowed) in the GraphQL-Specs (version: 2018)
     * @param ValidationContext $validationContext
     * @return void
     */
    public function validate(ValidationContext $validationContext): void
    {
        // This rule is for performance reasons not implemented again, since Executor::execute
        // validates this when coercing variables (while building the ExecutionContext) via
        // Values::getArgumentValues(...)
    }
}
