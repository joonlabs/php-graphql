<?php

namespace GraphQL\Validation\Rules;

use GraphQL\Errors\GraphQLError;
use GraphQL\Errors\ValidationError;
use GraphQL\Types\GraphQLType;
use GraphQL\Validation\DocumentUtils;
use GraphQL\Validation\ValidationContext;

class FragmentsMustNotFormCycles extends ValidationRule
{
    /**
     * Implements the rule specified under 5.5.2.2 (Fragments Must Not Form Cycles) in the GraphQL-Specs (version: 2018)
     * @param ValidationContext $validationContext
     * @return array
     */
    public function validate(ValidationContext $validationContext): void
    {
        $document = $validationContext->getDocument();
        $schema = $validationContext->getSchema();

        $fragmentDefinitions = DocumentUtils::getAllNodesOfKind($document, "FragmentDefinition");

        // build $fragmentDefinitionsMap
        $fragmentDefinitionsMap = [];
        foreach ($fragmentDefinitions as $fragmentDefinition){
            $fragmentDefinitionsMap[$fragmentDefinition["name"]["value"]] = $fragmentDefinition;
        }

        foreach ($fragmentDefinitions as $fragmentDefinition){
            $visited = [];
            $this->detectCycles($fragmentDefinition, $visited, null, $fragmentDefinitionsMap);
        }
    }

    private function detectCycles(array $fragmentDefinition, array &$visited, ?array $lastFragmentSpread, array $fragmentDefinitionsMap){
        // check if we've seen this fragment already
        if(in_array($fragmentDefinition, $visited)){
            $this->addError(
                new ValidationError(
                    "Fragments must not form cycles.",
                    $lastFragmentSpread
                )
            );
            return;
        }
        // append fragment to visited list
        $visited[] = $fragmentDefinition;

        $selectionSet = $fragmentDefinition["selectionSet"];
        $selections = $selectionSet["selections"];
        $i = 0;
        while(($selections[$i]??null)!==null){
            $selection = $selections[$i];

            if($selection["kind"]==="FragmentSpread"){
                $newFragmentDefinition = $fragmentDefinitionsMap[$selection["name"]["value"]] ?? null;
                $this->detectCycles($newFragmentDefinition, $visited, $selection, $fragmentDefinitionsMap);
            }

            if($selection["kind"]==="Field"){
                $selections = array_merge($selections, $selection["selectionSet"]["selections"] ?? []);
            }

            $i++;
        }
    }
}

?>