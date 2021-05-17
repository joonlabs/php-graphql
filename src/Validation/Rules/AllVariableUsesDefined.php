<?php

namespace GraphQL\Validation\Rules;

use GraphQL\Errors\ValidationError;
use GraphQL\Utilities\KeyMap;
use GraphQL\Validation\DocumentUtils;
use GraphQL\Validation\ValidationContext;

class AllVariableUsesDefined extends ValidationRule
{
    /**
     * Implements the rule specified under 5.8.3 (All Variable Uses Defined) in the GraphQL-Specs (version: 2018)
     * @param ValidationContext $validationContext
     * @return void
     */
    public function validate(ValidationContext $validationContext): void
    {
        $document = $validationContext->getDocument();
        $definitions = $document["definitions"];

        foreach ($definitions as $definition) {
            if ($definition["kind"] !== "OperationDefinition") continue;

            $variableDefinitions = KeyMap::map($definition["variableDefinitions"], function ($definition) {
                return $definition["variable"]["name"]["value"];
            });

            $variableUsages = KeyMap::map(DocumentUtils::getAllNodesOfKind($definition["selectionSet"], "Variable"), function ($definition) {
                return $definition["name"]["value"];
            });

            $variableUsages = array_keys($variableUsages);
            $variableDefinitions = array_keys($variableDefinitions);

            foreach ($variableUsages as $usage) {
                if (!in_array($usage, $variableDefinitions)) {
                    $this->addError(
                        new ValidationError(
                            "Variable \"$usage\" is not defined.",
                            $definition
                        )
                    );
                }
            }
        }
    }
}
