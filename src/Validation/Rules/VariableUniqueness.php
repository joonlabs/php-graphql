<?php

namespace GraphQL\Validation\Rules;

use GraphQL\Errors\ValidationError;
use GraphQL\Validation\ValidationContext;

class VariableUniqueness extends ValidationRule
{
    /**
     * Implements the rule specified under 5.8.1 (Variable Uniqueness) in the GraphQL-Specs (version: 2018)
     * @param ValidationContext $validationContext
     * @return void
     */
    public function validate(ValidationContext $validationContext): void
    {
        $definitions = $validationContext->getDocument()["definitions"];

        foreach ($definitions as $definition) {
            if ($definition["kind"] !== "OperationDefinition") continue;

            $variableDefinitions = $definition["variableDefinitions"];
            $seenKeys = [];

            foreach ($variableDefinitions as $variableDefinition) {
                $definitionName = $variableDefinition["variable"]["name"]["value"];
                if (in_array($definitionName, $seenKeys)) {
                    $this->addError(
                        new ValidationError(
                            "There can be only one variable named \"$definitionName\".",
                            $variableDefinition
                        )
                    );
                } else {
                    $seenKeys[] = $definitionName;
                }
            }
        }
    }
}

