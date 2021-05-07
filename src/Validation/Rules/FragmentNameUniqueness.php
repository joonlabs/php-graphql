<?php

namespace GraphQL\Validation\Rules;

use GraphQL\Errors\GraphQLError;
use GraphQL\Errors\ValidationError;
use GraphQL\Types\GraphQLType;
use GraphQL\Validation\DocumentUtils;
use GraphQL\Validation\ValidationContext;

class FragmentNameUniqueness extends ValidationRule
{
    /**
     * Implements the rule specified under 5.4.2 (Argument Uniqueness) in the GraphQL-Specs (version: 2018)
     * @param ValidationContext $validationContext
     * @return array
     */
    public function validate(ValidationContext $validationContext): void
    {
        $document = $validationContext->getDocument();
        $schema = $validationContext->getSchema();

        $fragmentDefinitions = DocumentUtils::getAllNodesOfKind($document, "FragmentDefinition");

        $seenFragmentNames = [];
        foreach ($fragmentDefinitions as $fragmentDefinition){
            $fragmentName = $fragmentDefinition["name"]["value"];
            if(!in_array($fragmentName, $seenFragmentNames)){
                $seenFragmentNames[] = $fragmentName;
            }else{
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

?>