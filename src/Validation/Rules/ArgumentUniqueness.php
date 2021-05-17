<?php

namespace GraphQL\Validation\Rules;

use GraphQL\Errors\ValidationError;
use GraphQL\Validation\DocumentUtils;
use GraphQL\Validation\ValidationContext;

class ArgumentUniqueness extends ValidationRule
{
    /**
     * Implements the rule specified under 5.4.2 (Argument Uniqueness) in the GraphQL-Specs (version: 2018)
     * @param ValidationContext $validationContext
     * @return void
     */
    public function validate(ValidationContext $validationContext): void
    {
        $document = $validationContext->getDocument();

        //var_dump($document);
        $argumentLists = DocumentUtils::getAllNodesOfKey($document, "arguments");

        foreach ($argumentLists as $argumentList) {
            $seenArguments = [];
            foreach ($argumentList as $arg) {
                $argName = $arg["name"]["value"];
                if (!in_array($argName, $seenArguments)) {
                    $seenArguments[] = $argName;
                } else {
                    $this->addError(
                        new ValidationError(
                            "There can be only one argument named \"$argName\".",
                            $arg
                        )
                    );
                }
            }
        }
    }
}
