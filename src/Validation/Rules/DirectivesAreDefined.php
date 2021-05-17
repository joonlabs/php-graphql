<?php

namespace GraphQL\Validation\Rules;

use GraphQL\Errors\ValidationError;
use GraphQL\Validation\DocumentUtils;
use GraphQL\Validation\ValidationContext;

class DirectivesAreDefined extends ValidationRule
{
    /**
     * Implements the rule specified under 5.2.1.1 in the GraphQL-Specs (version: 2018)
     * @param ValidationContext $validationContext
     * @return void
     */
    public function validate(ValidationContext $validationContext): void
    {
        $document = $validationContext->getDocument();
        $schema = $validationContext->getSchema();

        $directives = $schema->getDirectives();

        $directives = array_map(function ($directive) {
            return $directive["name"]["value"];
        }, $directives);

        // include "skip" and "include" by default
        $directives[] = "skip";
        $directives[] = "include";

        $wantedDirectives = DocumentUtils::getAllNodesOfKey($document, "directives");

        foreach ($wantedDirectives as $wantedDirectiveList) {
            foreach ($wantedDirectiveList as $directive) {
                $directiveName = $directive["name"]["value"] ?? null;
                if (!in_array($directiveName, $directives)) {
                    // directiv not known
                    $this->addError(
                        new ValidationError(
                            "Unknown directive \"$directiveName\".",
                            $directive
                        )
                    );
                }
            }
        }
    }
}

