<?php

namespace GraphQL\Validation\Rules;

use GraphQL\Errors\GraphQLError;
use GraphQL\Errors\ValidationError;
use GraphQL\Validation\ValidationContext;

class LoneAnonymousOperation extends ValidationRule
{
    /**
     * Implements the rule specified under 5.2.2.1 in the GraphQL-Specs (version: 2018)
     * @param ValidationContext $validationContext
     * @return array
     */
    public function validate(ValidationContext $validationContext): void
    {
        $definitions = $validationContext->getDocument()["definitions"];

        $unnamedOperations = 0;

        foreach ($definitions as $definition) {
            $definitionName = $definition["name"] ?? null;
            $definitionKind = $definition["kind"] ?? null;
            if ($definitionKind === "OperationDefinition" and $definitionName === null) {
                $unnamedOperations++;

                // if more than one
                if ($unnamedOperations > 1) {
                    $this->addError(
                        new ValidationError(
                            "This anonymous operation must be the only defined operation.",
                            $definition
                        )
                    );
                }
            }
        }
    }
}

?>