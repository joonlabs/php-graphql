<?php

namespace GraphQL\Validation\Rules;

use GraphQL\Errors\ValidationError;
use GraphQL\Types\GraphQLType;
use GraphQL\Utilities\Suggestions;
use GraphQL\Validation\DocumentUtils;
use GraphQL\Validation\ValidationContext;

class FieldSelectionsOnObjectsInterfacesAndUnionTypes_FragmentSpreadTypeExistence extends ValidationRule
{
    /**
     * Implements the rule specified under 5.3.1 (Field Selections on Objects, Interfaces, and Union Types) in the GraphQL-Specs (version: 2018),
     * also implements the rule specified under 5.5.1.2 (Fragment Spread Type Existence) in the GraphQL-Specs (version: 2018)
     * @param ValidationContext $validationContext
     * @return void
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
                if (!in_array($selectedFieldName, array_keys($objectType->getFields()))) {
                    // if the name of the selected field does not exist in the object's field list -> create error
                    $this->addError(
                        new ValidationError(
                            "Cannot query field \"" . $selectedFieldName . "\" on type \"" . $objectType->getName() . "\".",
                            $selection
                        )
                    );
                } else {
                    // if the field has a sub selection -> recursively call this function with the sub selection
                    $subSelectionSet = $selection["selectionSet"];
                    if ($subSelectionSet !== null and count($subSelectionSet) > 0) {
                        // get the type of the sub selection
                        $subType = $objectType->getFields()[$selectedFieldName]->getType();
                        // if this type is a GrappQLList or GraphQLNonNull get the inner type (notice: abstract types support getFields())
                        if ($subType->isWrappingType()) {
                            $subType = $subType->getInnerType();
                        }
                        // call the function to check the sub selection
                        $this->validateFieldsInSelectionSet($subSelectionSet, $subType, $validationContext);
                    }
                }
            }

            // handle inlinefragment selection
            if ($selectionKind === "InlineFragment") {
                $typeConditionName = $selection["typeCondition"]["name"]["value"] ?? null;
                $typeMap = $validationContext->getSchema()->getTypeMap();

                // check if TypeCondition of InlineFragment is in Types
                if (!in_array($typeConditionName, array_keys($typeMap))) {
                    $suggestions = Suggestions::suggest($typeConditionName, array_keys($typeMap));
                    $this->addError(
                        new ValidationError(
                            "Unknown type \"$typeConditionName\"." . Suggestions::didYouMean($suggestions),
                            $selection
                        )
                    );
                } else {
                    // call the function to check the InlineFragment's SelectionSet
                    $subSelectionSet = $selection["selectionSet"];
                    $subType = $typeMap[$typeConditionName];
                    // if this type is a GrappQLList or GraphQLNonNull get the inner type (notice: abstract types support getFields())
                    if ($subType->isWrappingType()) {
                        $subType = $subType->getInnerType();
                    }


                    // check if subType is allowed here (GraphQL Spec Rule 5.5.2.3 - Fragment Spread Is Possible)
                    if (($objectType->isObjectType() and $objectType->getName() !== $subType->getName()) // check if object types are same
                        || ($objectType->isAbstractType() and !$validationContext->getSchema()->isSubType($objectType, $subType)) // check if abstract type has this subType as a sub type
                    ) {
                        // if any rule violated -> error
                        $this->addError(
                            new ValidationError(
                                "Fragment cannot be spread here as objects of type \"" . $subType->getName() . "\" can never be of type \"" . $objectType->getName() . "\".",
                                $selection
                            )
                        );
                    }

                    // call the function to check the sub selection
                    $this->validateFieldsInSelectionSet($subSelectionSet, $subType, $validationContext);
                }
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

                        // check if TypeCondition of InlineFragment is in Types
                        if (!in_array($typeConditionName, array_keys($typeMap))) {
                            $suggestions = Suggestions::suggest($typeConditionName, array_keys($typeMap));
                            $this->addError(
                                new ValidationError(
                                    "Unknown type \"$typeConditionName\"." . Suggestions::didYouMean($suggestions),
                                    $fragmentDefinition
                                )
                            );
                        } else {
                            // call the function to check the InlineFragment's SelectionSet
                            $subSelectionSet = $fragmentDefinition["selectionSet"];
                            $subType = $typeMap[$typeConditionName];

                            // check if subType is allowed here (GraphQL Spec Rule 5.5.2.3 - Fragment Spread Is Possible)
                            if (($objectType->isObjectType() and $objectType->getName() !== $subType->getName()) // check if object types are same
                                || ($objectType->isAbstractType() and !$validationContext->getSchema()->isSubType($objectType, $subType)) // check if abstract type has this subType as a sub type
                            ) {
                                // if any rule violated -> error
                                $this->addError(
                                    new ValidationError(
                                        "Fragment \"$fragmentSpreadName\" cannot be spread here as objects of type \"" . $subType->getName() . "\" can never be of type \"" . $objectType->getName() . "\".",
                                        $selection
                                    )
                                );
                            }

                            // if this type is a GrappQLList or GraphQLNonNull get the inner type (notice: abstract types support getFields())
                            if ($subType->isWrappingType()) {
                                $subType = $subType->getInnerType();
                            }
                            // call the function to check the sub selection
                            $this->validateFieldsInSelectionSet($subSelectionSet, $subType, $validationContext);
                        }
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

