<?php

namespace GraphQL\Validation\Rules;

use GraphQL\Errors\GraphQLError;
use GraphQL\Errors\ValidationError;
use GraphQL\Utilities\KeyMap;
use GraphQL\Validation\DocumentUtils;
use GraphQL\Validation\ValidationContext;

class DirectivesAreUniquePerLocation extends ValidationRule
{
    /**
     * Implements the rule specified under 5.2.1.1 in the GraphQL-Specs (version: 2018)
     * @param ValidationContext $validationContext
     * @return array
     */
    public function validate(ValidationContext $validationContext): void
    {
        $document = $validationContext->getDocument();
        $schema = $validationContext->getSchema();

        $directives = $schema->getDirectives();

        $directives = array_map(function($directive){
            return $directive["name"]["value"];
        }, $directives);

        // include "skip" and "include" by default
        $directives[] = "skip";
        $directives[] = "include";

        $wantedDirectives = DocumentUtils::getAllNodesOfKey($document, "directives");

        foreach ($wantedDirectives as $wantedDirectiveList) {
            $seenDirectiveNames = [];
            foreach($wantedDirectiveList as $directive){
                $directiveName = $directive["name"]["value"] ?? null;
                if(in_array($directiveName, $seenDirectiveNames)){
                    // directiv already used in this spot
                    $this->addError(
                        new ValidationError(
                            "Directive \"$directiveName\" cannot be applied more than once.",
                            $directive
                        )
                    );
                }
                $seenDirectiveNames[] = $directiveName;
            }
        }
    }
}

?>