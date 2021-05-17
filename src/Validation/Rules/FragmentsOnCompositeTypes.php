<?php

namespace GraphQL\Validation\Rules;

use GraphQL\Errors\ValidationError;
use GraphQL\Utilities\Suggestions;
use GraphQL\Validation\DocumentUtils;
use GraphQL\Validation\ValidationContext;

class FragmentsOnCompositeTypes extends ValidationRule
{
    /**
     * Implements the rule specified under 5.4.2 (Argument Uniqueness) in the GraphQL-Specs (version: 2018)
     * @param ValidationContext $validationContext
     * @return void
     */
    public function validate(ValidationContext $validationContext): void
    {
        $document = $validationContext->getDocument();
        $schema = $validationContext->getSchema();

        $fragmentDefinitions = array_merge(
            DocumentUtils::getAllNodesOfKind($document, "FragmentDefinition"),
            DocumentUtils::getAllNodesOfKind($document, "InlineFragment")
        );
        $typeMap = $schema->getTypeMap();

        foreach ($fragmentDefinitions as $fragmentDefinition) {
            $typeCondition = $fragmentDefinition["typeCondition"]["name"]["value"];

            // check if type exists
            if (!array_key_exists($typeCondition, $typeMap)) {
                $suggestions = Suggestions::suggest($typeCondition, array_keys($typeMap));
                $this->addError(
                    new ValidationError(
                        "Unknown type \"$typeCondition\"." . Suggestions::didYouMean($suggestions),
                        $fragmentDefinition
                    )
                );
                continue;
            }

            // check if type is composite type
            if (!$typeMap[$typeCondition]->isInterfaceType()
                && !$typeMap[$typeCondition]->isUnionType()
                && !$typeMap[$typeCondition]->isObjectType()) {

                $this->addError(
                    new ValidationError(
                        "Fragment"
                        . (($fragmentDefinition["name"] ?? null) !== null ? " \"" . $fragmentDefinition["name"]["value"] . "\"" : "")
                        . " cannot condition on non composite type \"$typeCondition\".",
                        $fragmentDefinition
                    )
                );
            }
        }
    }
}

