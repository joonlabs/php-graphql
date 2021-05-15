<?php

namespace GraphQL\Validation\Rules;

use GraphQL\Errors\GraphQLError;
use GraphQL\Errors\ValidationError;
use GraphQL\Validation\ValidationContext;

class AnyOperationDefined extends ValidationRule
{
    /**
     * Implements a custom rule to ensure that an operation is present
     * @param ValidationContext $validationContext
     * @return array
     */
    public function validate(ValidationContext $validationContext): void
    {
        $document = $validationContext->getDocument();

        $foundAnyOperationDefinition = false;

        foreach ($document["definitions"] as $definition) {
            if ($definition["kind"] === "OperationDefinition") $foundAnyOperationDefinition = true;
        }

        if (!$foundAnyOperationDefinition) {
            $this->addError(
                new ValidationError(
                    "No operation found. Provide at least one operation."
                )
            );
        }
    }
}

?>