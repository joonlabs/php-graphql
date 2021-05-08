<?php

namespace GraphQL\Validation\Rules;

use GraphQL\Errors\GraphQLError;
use GraphQL\Errors\ValidationError;
use GraphQL\Utilities\KeyMap;
use GraphQL\Validation\DocumentUtils;
use GraphQL\Validation\ValidationContext;

class DisableIntrospection extends ValidationRule
{
    /**
     * This rule disables the introspection of the graphql api
     * @param ValidationContext $validationContext
     * @return array
     */
    public function validate(ValidationContext $validationContext): void
    {
        $definitions = $validationContext->getDocument()["definitions"];

        foreach ($definitions as $definition){
            if($definition["kind"]==="OperationDefinition"){
                $selections = $definition["selectionSet"]["selections"];
                foreach ($selections as $selection){
                    if($selection["name"]["value"]==="__schema"){
                        $this->addError(
                            new ValidationError(
                                "Introspection is disabled in this setup.",
                                $definition
                            )
                        );
                    }
                }
            }
        }
    }
}

?>