<?php

namespace GraphQL\Validation\Rules;

use GraphQL\Errors\ValidationError;
use GraphQL\Utilities\Suggestions;
use GraphQL\Validation\ValidationContext;

class VariablesAreInputTypes extends ValidationRule
{
    /**
     * Implements the rule specified under 5.8.2 (Variables Are Input Types) in the GraphQL-Specs (version: 2018)
     * @param ValidationContext $validationContext
     * @return void
     */
    public function validate(ValidationContext $validationContext): void
    {
        $definitions = $validationContext->getDocument()["definitions"];
        $typeMap = $validationContext->getSchema()->getTypeMap();

        foreach ($definitions as $definition) {
            if ($definition["kind"] !== "OperationDefinition") continue;

            $variableDefinitions = $definition["variableDefinitions"];

            foreach ($variableDefinitions as $variableDefinition) {
                $type = $variableDefinition["type"];
                $name = $variableDefinition["variable"]["name"]["value"];

                while ($type["kind"] === "ListType" || $type["kind"] === "NonNullType") {
                    $type = $type["type"];
                }

                $typeName = $type["name"]["value"];
                $type = $typeMap[$typeName] ?? null;
                if ($type === null) {
                    $suggestions = Suggestions::suggest($typeName, array_keys($typeMap));
                    $this->addError(
                        new ValidationError(
                            "Unknown type \"$typeName\"." . Suggestions::didYouMean($suggestions),
                            $definition
                        )
                    );
                } else if (!$type->isInputType()) {
                    $this->addError(
                        new ValidationError(
                            "Variable \"$name\" cannot be non-input type \"" . $type->getName() . "\".",
                            $definition
                        )
                    );
                }
            }

        }
    }

}

