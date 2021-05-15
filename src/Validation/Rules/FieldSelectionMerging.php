<?php

namespace GraphQL\Validation\Rules;

use GraphQL\Errors\GraphQLError;
use GraphQL\Errors\ValidationError;
use GraphQL\Types\GraphQLObjectType;
use GraphQL\Types\GraphQLType;
use GraphQL\Utilities\KeyMap;
use GraphQL\Utilities\Suggestions;
use GraphQL\Validation\DocumentUtils;
use GraphQL\Validation\ValidationContext;

class FieldSelectionMerging extends ValidationRule
{
    /**
     * Implements the rule specified under 5.3.2 (Field Selection Merging) in the GraphQL-Specs (version: 2018),
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
                    $this->fieldsInSetCanMerge($definitionSelectionSet, $schema->getQueryType(), $validationContext);
                } else if ($definitionOperation === "mutation") {
                    $this->fieldsInSetCanMerge($definitionSelectionSet, $schema->getMutationType(), $validationContext);
                }
            }
        }
    }

    /**
     * Returns whether fields in a selection set can merge or not
     * @param array $selectionSet
     * @param GraphQLType $objectType
     * @param ValidationContext $validationContext
     */
    private function fieldsInSetCanMerge(array $selectionSet, GraphQLType $objectType, ValidationContext $validationContext)
    {
        // if the field does not support getting sub fields -> return
        if (!method_exists($objectType, "getFields")) return;

        $allSelectedFields = [];

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

                // add to $allSelectedFields
                $allSelectedFields[] = $selection;

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
                    $this->fieldsInSetCanMerge($subSelectionSet, $subType, $validationContext);
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
                    // check if abstract subType is allowed here (GraphQL Spec Rule 5.5.2.3.3 - Abstract Spreads In Abstract Scope)
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

                    // collect fields of inline fragment and append those to the $allSelectedFields
                    $fields = $this->collectFieldsInInlineFragment($selection, $validationContext);
                    $allSelectedFields = array_merge($allSelectedFields, $fields);
                }
            }

            // handle inlinefragment selection
            if ($selectionKind === "FragmentSpread") {
                $fragmentSpreadName = $selection["name"]["value"];
                $fields = $this->collectFieldsInFragment($fragmentSpreadName, $validationContext);
                $allSelectedFields = array_merge($allSelectedFields, $fields);
            }
        }


        // collect fields with same name / alias into one array
        $fieldsPerName = [];
        foreach ($allSelectedFields as $selectedField) {
            $selectedName = $selectedField["alias"]["value"] ?? $selectedField["name"]["value"];

            if (!array_key_exists($selectedName, $fieldsPerName)) {
                $fieldsPerName[$selectedName] = [];
            }

            if (array_key_exists($selectedName, $objectType->getFields())) {
                $fieldsPerName[$selectedName][] = $selectedField;
            }
        }

        foreach ($fieldsPerName as $selectedName => $selectedFields) {
            if (count($selectedFields) > 1) {
                // collect all possible permutations of fields that might be amigouse
                $permutations = $this->getPermutations($selectedFields);
                foreach ($permutations as $permutation) {
                    $sameResponseShape = $this->sameResponseShape($permutation[0], $permutation[1], $objectType);
                    if (!$sameResponseShape) {
                        $this->addError(
                            new ValidationError(
                                "Fields \"$selectedName\" conflict because " . $permutation[0]["name"]["value"] . " and " . $permutation[1]["name"]["value"] . " are different fields. Use different aliases on the fields to fetch both if this was intentional.",
                                $permutation[1]
                            )
                        );
                        continue;
                    }

                    // check if arguments are the same set
                    $argumentsA = $permutation[0]["arguments"];
                    $argumentsB = $permutation[1]["arguments"];

                    // remove locations from arguments from fieldA
                    array_walk_recursive($argumentsA, function (&$item, $key) {
                        if ($key === "line" || $key === "column") $item = null;
                    });
                    // remove locations from arguments from fieldB
                    array_walk_recursive($argumentsB, function (&$item, $key) {
                        if ($key === "line" || $key === "column") $item = null;
                    });

                    // check if both arguments are the same
                    if ($argumentsA != $argumentsB) {
                        $this->addError(
                            new ValidationError(
                                "Fields \"$selectedName\" conflict because they have differing arguments. Use different aliases on the fields to fetch both if this was intentional.",
                                $permutation[1]
                            )
                        );
                        continue;
                    }
                }
            }
        }
    }

    /**
     * Returns, whether fieldA and fieldB share the same response shape
     * @param array $fieldA
     * @param array $fieldB
     * @param GraphQLObjectType $parentType
     * @return bool
     */
    private function sameResponseShape(array $fieldA, array $fieldB, GraphQLType $parentType)
    {
        // step 1
        $typeA = $parentType->getFields()[$fieldA["name"]["value"]]->getType();

        // step 2
        $typeB = $parentType->getFields()[$fieldB["name"]["value"]]->getType();

        while ($typeA->isNonNullType() || $typeA->isListType() || $typeB->isNonNullType() || $typeB->isListType()) {
            // step 3
            if ($typeA->isNonNullType() || $typeB->isNonNullType()) {
                if (!$typeA->isNonNullType() || !$typeB->isNonNullType()) return false;
                $typeA = $typeA->getInnerType();
                $typeB = $typeB->getInnerType();
            }

            // step 4
            if ($typeA->isListType() || $typeB->isListType()) {
                if (!$typeA->isListType() || !$typeB->isListType()) return false;
                $typeA = $typeA->getInnerType();
                $typeB = $typeB->getInnerType();
            }
        }

        // step 5
        if ($typeA->isScalarType() || $typeA->isEnumType() || $typeB->isScalarType() || $typeB->isEnumType()) {
            return $typeA->getName() === $typeB->getName();
        }

        // step 6
        if (!$typeA->isCompositeType() || !$typeB->isCompositeType()) {
            return false;
        }

        // step 7-9 are implemented in higher order functions

        return true;
    }

    /**
     * Returns all possible permutations
     * @param array $arr
     * @return array
     */
    private function getPermutations(array $arr)
    {
        $perms = [];
        for ($i = 0; $i < count($arr); $i++) {
            for ($j = $i + 1; $j < count($arr); $j++) {
                $perms[] = [$arr[$i], $arr[$j]];
            }
        }
        return $perms;
    }

    /**
     * Returns all fields in an inline fragment
     * @param array $inlineFragment
     * @param ValidationContext $validationContext
     * @return array
     */
    private function collectFieldsInInlineFragment(array $inlineFragment, ValidationContext $validationContext)
    {
        $fields = [];

        $objectType = $validationContext->getSchema()->getTypeMap()[$inlineFragment["typeCondition"]["name"]["value"]];
        if (!method_exists($objectType, "getFields")) return [];

        foreach ($inlineFragment["selectionSet"]["selections"] as $selection) {
            if ($selection["kind"] === "Field") {
                $fields[] = $selection;

                // if the field has a sub selection -> recursively call this function with the sub selection
                $subSelectionSet = $selection["selectionSet"];
                if ($subSelectionSet !== null and count($subSelectionSet) > 0) {
                    // get the type of the sub selection
                    $subType = $objectType->getFields()[$selection["name"]["value"]]->getType();
                    // if this type is a GrappQLList or GraphQLNonNull get the inner type (notice: abstract types support getFields())
                    while ($subType->isWrappingType()) {
                        $subType = $subType->getInnerType();
                    }
                    // call the function to check the sub selection
                    $this->fieldsInSetCanMerge($subSelectionSet, $subType, $validationContext);
                }
            }
            if ($selection["kind"] === "InlineFragment") {
                // get the type of the sub selection
                $fields = array_merge($fields, $this->collectFieldsInInlineFragment($selection, $validationContext));
            }
            if ($selection["kind"] === "FragmentSpread") {
                $fields = array_merge($fields, $this->collectFieldsInFragment($selection["name"]["value"], $validationContext));
            }
        }

        return $fields;
    }

    /**
     * Returns all fields in an fragment
     * @param string $fragmentName
     * @param ValidationContext $validationContext
     * @return array
     */
    private function collectFieldsInFragment(string $fragmentName, ValidationContext $validationContext)
    {
        $fields = [];

        $fragments = DocumentUtils::getAllNodesOfKind($validationContext->getDocument(), "FragmentDefinition");
        $fragments = KeyMap::map($fragments, function ($fragment) {
            return $fragment["name"]["value"];
        });

        $fragment = $fragments["$fragmentName"];

        $objectType = $validationContext->getSchema()->getTypeMap()[$fragment["typeCondition"]["name"]["value"]];
        if (!method_exists($objectType, "getFields")) return [];

        foreach ($fragment["selectionSet"]["selections"] as $selection) {
            if ($selection["kind"] === "Field") {
                $fields[] = $selection;

                // if the field has a sub selection -> recursively call this function with the sub selection
                $subSelectionSet = $selection["selectionSet"];
                if ($subSelectionSet !== null and count($subSelectionSet) > 0) {
                    // get the type of the sub selection
                    $subType = $objectType->getFields()[$selection["name"]["value"]]->getType();
                    // if this type is a GrappQLList or GraphQLNonNull get the inner type (notice: abstract types support getFields())
                    if ($subType->isWrappingType()) {
                        $subType = $subType->getInnerType();
                    }
                    // call the function to check the sub selection
                    $this->fieldsInSetCanMerge($subSelectionSet, $subType, $validationContext);
                }
            }
            if ($selection["kind"] === "InlineFragment") {
                $fields = array_merge($fields, $this->collectFieldsInInlineFragment($selection, $validationContext));
            }
            if ($selection["kind"] === "FragmentSpread") {
                $fields = array_merge($fields, $this->collectFieldsInFragment($selection["name"]["value"], $validationContext));
            }
        }

        return $fields;
    }
}

?>