<?php

namespace GraphQL\Validation\Rules;

use GraphQL\Errors\ValidationError;
use GraphQL\Validation\DocumentUtils;
use GraphQL\Validation\ValidationContext;

class FragmentNameUniqueness extends ValidationRule
{
    /**
     * Implements the rule specified under 5.4.2 (Argument Uniqueness) in the GraphQL-Specs (version: 2018)
     * @param ValidationContext $validationContext
     * @return void
     */
    public function validate(ValidationContext $validationContext): void
    {
        $document = $validationContext->getDocument();

        $fragmentDefinitions = DocumentUtils::getAllNodesOfKind($document, "FragmentDefinition");

        $seenFragmentNames = [];
        foreach ($fragmentDefinitions as $fragmentDefinition) {
            $fragmentName = $fragmentDefinition["name"]["value"];
            if (!in_array($fragmentName, $seenFragmentNames)) {
                $seenFragmentNames[] = $fragmentName;
            } else {
                $this->addError(
                    new ValidationError(
                        "There can be only one fragment named \"$fragmentName\".",
                        $fragmentDefinition
                    )
                );
            }
        }
    }
}

