<?php

namespace GraphQL\Validation\Rules;

use GraphQL\Errors\ValidationError;
use GraphQL\Validation\DocumentUtils;
use GraphQL\Validation\ValidationContext;

class FragmentsMustBeUsed extends ValidationRule
{
    /**
     * Implements the rule specified under 5.5.1.4 (Fragments Must Be Used) in the GraphQL-Specs (version: 2018)
     * @param ValidationContext $validationContext
     * @return void
     */
    public function validate(ValidationContext $validationContext): void
    {
        $document = $validationContext->getDocument();

        $fragmentDefinitions = DocumentUtils::getAllNodesOfKind($document, "FragmentDefinition");
        $fragmentSpreads = DocumentUtils::getAllNodesOfKind($document, "FragmentSpread");


        // create assoc array of all fragment definitions
        $usedFragmentNames = [];
        foreach ($fragmentDefinitions as $fragmentDefinition) {
            $fragmentName = $fragmentDefinition["name"]["value"];
            $usedFragmentNames[$fragmentName] = $fragmentDefinition;
        }


        // mark used fragments as used
        foreach ($fragmentSpreads as $fragmentSpread) {
            $fragmentSpreadName = $fragmentSpread["name"]["value"];
            $usedFragmentNames[$fragmentSpreadName] = true;
        }

        foreach ($usedFragmentNames as $fragName => $used) {
            if ($used !== true) {
                $this->addError(
                    new ValidationError(
                        "Fragment \"$fragName\" is defined but never used.",
                        $used // here used contains not a bool but the original fragment definition
                    )
                );
            }
        }
    }
}

