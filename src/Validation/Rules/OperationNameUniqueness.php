<?php

namespace GraphQL\Validation\Rules;

use GraphQL\Errors\ValidationError;
use GraphQL\Validation\ValidationContext;

class OperationNameUniqueness extends ValidationRule
{
    /**
     * Implements the rule specified under 5.2.1.1 in the GraphQL-Specs (version: 2018)
     * @param ValidationContext $validationContext
     * @return array
     */
    public function validate(ValidationContext $validationContext) : void
    {
        $definitions = $validationContext->getDocument()["definitions"];

        $namedDefinitionsNames = [];

        foreach($definitions as $definition){
            $definitionName = $definition["name"] ?? null;
            $definitionKind = $definition["kind"] ?? null;

            if($definitionKind === "OperationDefinition" and $definitionName["value"]!==null){
                if(!in_array($definitionName["value"], $namedDefinitionsNames)){
                    // operation is not known until now -> add it to the array
                    $namedDefinitionsNames[] = $definitionName["value"];
                }else{
                    // operation name already known
                    $this->addError(
                        new ValidationError(
                            "Each named operation definition must be unique. \"" . $definitionName["value"] . "\" is already defined.",
                            $definitionName
                        )
                    );
                }
            }
        }
    }
}

?>