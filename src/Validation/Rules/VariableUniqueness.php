<?php

namespace GraphQL\Validation\Rules;

use GraphQL\Errors\GraphQLError;
use GraphQL\Errors\ValidationError;
use GraphQL\Utilities\KeyMap;
use GraphQL\Validation\ValidationContext;

class VariableUniqueness extends ValidationRule
{
    /**
     * Implements the rule specified under 5.8.1 (Variable Uniqueness) in the GraphQL-Specs (version: 2018)
     * @param ValidationContext $validationContext
     * @return array
     */
    public function validate(ValidationContext $validationContext): void
    {
        $definitions = $validationContext->getDocument()["definitions"];

        foreach ($definitions as $definition) {
            if ($definition["kind"] !== "OperationDefinition") continue;

            $variableDefinitions = $definition["variableDefinitions"];
            $seenKeys = [];

            foreach ($variableDefinitions as $definition) {
                $definitionName = $definition["variable"]["name"]["value"];
                if (in_array($definitionName, $seenKeys)) {
                    $this->addError(
                        new ValidationError(
                            "There can be only one variable named \"$definitionName\".",
                            $definition
                        )
                    );
                } else {
                    $seenKeys[] = $definitionName;
                }
            }
        }
    }
}

?>