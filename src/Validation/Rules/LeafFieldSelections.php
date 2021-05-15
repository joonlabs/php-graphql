<?php

namespace GraphQL\Validation\Rules;

use GraphQL\Errors\GraphQLError;
use GraphQL\Errors\ValidationError;
use GraphQL\Types\GraphQLType;
use GraphQL\Validation\DocumentUtils;
use GraphQL\Validation\ValidationContext;

class LeafFieldSelections extends ValidationRule
{
    /**
     * Implements the rule specified under 5.3.3 (Leaf Field Selection) in the GraphQL-Specs (version: 2018)
     * @param ValidationContext $validationContext
     * @return array
     */
    public function validate(ValidationContext $validationContext): void
    {
        $definitions = $validationContext->getDocument()["definitions"];
        $schema = $validationContext->getSchema();

        foreach ($definitions as $definition) {
            $definitionKind = $definition["kind"] ?? null;
            $definitionOperation = $definition["operation"] ?? null;
            $definitionSelectionSet = $definition["selectionSet"] ?? null;
            if ($definitionKind === "OperationDefinition") {
                if ($definitionOperation === "query") {
                    $this->validateFieldsInSelectionSet($definitionSelectionSet, $schema->getQueryType(), $validationContext);
                } else if ($definitionOperation === "mutation") {
                    $this->validateFieldsInSelectionSet($definitionSelectionSet, $schema->getMutationType(), $validationContext);
                }

            }
        }
    }

    private function validateFieldsInSelectionSet(array $selectionSet, GraphQLType $objectType, ValidationContext $validationContext)
    {
        // if the field does not support getting sub fields -> return
        if (!method_exists($objectType, "getFields")) return;

        foreach ($selectionSet["selections"] as $selection) {
            $selectionKind = $selection["kind"];
            // handle field selection
            if ($selectionKind === "Field") {
                $selectedFieldName = $selection["name"]["value"];
                // check if field is an internal field and skip this field
                if ($selectedFieldName === "__typename"
                    || $selectedFieldName === "__schema"
                    || $selectedFieldName === "__type") {
                    continue;
                }
                $fieldType = $objectType->getFields()[$selectedFieldName]->getType();
                // if this type is a GrappQLList or GraphQLNonNull get the inner type (notice: abstract types support getFields())
                if ($fieldType->isWrappingType()) {
                    $fieldType = $fieldType->getInnerType();
                }

                $subSelectionSet = $selection["selectionSet"];
                // check if the current field's type is enum or scalar -> if yes, it must have no sub selection
                if ($fieldType->isEnumType() || $fieldType->isScalarType()) {
                    if (count($subSelectionSet["selections"] ?? []) > 0) {
                        $this->addError(
                            new ValidationError(
                                "Field \"$selectedFieldName\" must not have a selection since type \"" . $fieldType->getName() . "\" has no subfields.",
                                $selection
                            )
                        );
                    }
                }

                // check if the current field's type is interface, union, or object -> must have a sub selection
                if ($fieldType->isInterfaceType() || $fieldType->isUnionType() || $fieldType->isObjectType()) {
                    if (count($subSelectionSet["selections"] ?? []) === 0) {
                        $this->addError(
                            new ValidationError(
                                "Field \"$selectedFieldName\" of type \"" . $fieldType->getName() . "\" must have a selection of subfields. Did you mean \"$selectedFieldName { ... }\"?",
                                $selection
                            )
                        );
                    } else {
                        // call function recursivley on sub selection
                        $this->validateFieldsInSelectionSet($subSelectionSet, $fieldType, $validationContext);
                    }
                }

            }

            // handle inlinefragment selection
            if ($selectionKind === "InlineFragment") {
                $typeConditionName = $selection["typeCondition"]["name"]["value"] ?? null;
                $typeMap = $validationContext->getSchema()->getTypeMap();

                // call the function to check the InlineFragment's SelectionSet
                $subSelectionSet = $selection["selectionSet"];
                $subType = $typeMap[$typeConditionName];
                // if this type is a GrappQLList or GraphQLNonNull get the inner type (notice: abstract types support getFields())
                if ($subType->isWrappingType()) {
                    $subType = $subType->getInnerType();
                }
                // call the function to check the sub selection
                $this->validateFieldsInSelectionSet($subSelectionSet, $subType, $validationContext);
            }

            // handle inlinefragment selection
            if ($selectionKind === "FragmentSpread") {
                $fragmentDefinitions = DocumentUtils::getFragmentDefinitions($validationContext->getDocument());
                $fragmentSpreadName = $selection["name"]["value"];

                // check all fragment definitions if the fragmentSpread's name exists
                foreach ($fragmentDefinitions as $fragmentDefinition) {
                    $fragmentDefinitionName = $fragmentDefinition["name"]["value"];
                    if ($fragmentSpreadName === $fragmentDefinitionName) {
                        // if it exists -> call function recursively
                        $typeConditionName = $fragmentDefinition["typeCondition"]["name"]["value"] ?? null;
                        $typeMap = $validationContext->getSchema()->getTypeMap();

                        // call the function to check the InlineFragment's SelectionSet
                        $subSelectionSet = $fragmentDefinition["selectionSet"];
                        $subType = $typeMap[$typeConditionName];
                        // if this type is a GrappQLList or GraphQLNonNull get the inner type (notice: abstract types support getFields())
                        if ($subType->isWrappingType()) {
                            $subType = $subType->getInnerType();
                        }
                        // call the function to check the sub selection
                        $this->validateFieldsInSelectionSet($subSelectionSet, $subType, $validationContext);

                        // end the search for another fragment name
                        return;
                    }
                }
                // no fragment name matched -> no fragment defined with this name
                $this->addError(
                    new ValidationError(
                        "Unknown fragment name \"$fragmentSpreadName\".",
                        $selection
                    )
                );
            }
        }
    }
}

?>