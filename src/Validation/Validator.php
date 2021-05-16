<?php

namespace GraphQL\Validation;

use GraphQL\Schemas\Schema;
use GraphQL\Validation\Rules\AllVariablesUsed;
use GraphQL\Validation\Rules\AllVariableUsagesAreAllowed;
use GraphQL\Validation\Rules\AllVariableUsesDefined;
use GraphQL\Validation\Rules\AnyOperationDefined;
use GraphQL\Validation\Rules\ArgumentName;
use GraphQL\Validation\Rules\ArgumentUniqueness;
use GraphQL\Validation\Rules\DirectivesAreDefined;
use GraphQL\Validation\Rules\DirectivesAreUniquePerLocation;
use GraphQL\Validation\Rules\FieldSelectionMerging;
use GraphQL\Validation\Rules\FieldSelectionsOnObjectsInterfacesAndUnionTypes_FragmentSpreadTypeExistence;
use GraphQL\Validation\Rules\FragmentNameUniqueness;
use GraphQL\Validation\Rules\FragmentsMustBeUsed;
use GraphQL\Validation\Rules\FragmentsMustNotFormCycles;
use GraphQL\Validation\Rules\FragmentsOnCompositeTypes;
use GraphQL\Validation\Rules\InputObjectFieldNames;
use GraphQL\Validation\Rules\InputObjectFieldUniqueness;
use GraphQL\Validation\Rules\InputObjectRequiredFields;
use GraphQL\Validation\Rules\LeafFieldSelections;
use GraphQL\Validation\Rules\LoneAnonymousOperation;
use GraphQL\Validation\Rules\OperationNameUniqueness;
use GraphQL\Validation\Rules\RequiredArguments;
use GraphQL\Validation\Rules\ValidationRule;
use GraphQL\Validation\Rules\ValuesOfCorrectType;
use GraphQL\Validation\Rules\VariablesAreInputTypes;
use GraphQL\Validation\Rules\VariableUniqueness;

class Validator
{
    private $errors = [];
    private $additionalValidationRules = [];

    /**
     * Takes a schema and a parsed document and validates them against each other.
     * @param Schema $schema
     * @param array $document
     * @return void
     */
    public function validate(Schema $schema, array $document): void
    {
        // create validation context
        $validationContext = new ValidationContext($schema, $document);

        $this->errors = [];
        // check all validation rules
        foreach ($this->getAllValidationRules() as $validationRule) {
            // validate ValidationContext against ValiudationRule
            $validationRule->validate($validationContext);
            // check for validation
            if ($validationRule->violated()) {
                $this->errors = array_merge($this->errors, $validationRule->getErrors());
                // end for loop, as other rules may depend on this rule
                break;
            }
        }
    }

    public function addAdditionalValidationRule(ValidationRule $validationRule)
    {
        $this->additionalValidationRules[] = $validationRule;
    }

    /**
     * Returns whether the validation went successfull
     * @return bool
     */
    public function documentIsValid(): bool
    {
        return count($this->errors) === 0;
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Returns all ValidationRules necessary to check the ValidationContext against.
     * @return ValidationRule[]
     */
    private function getAllValidationRules()
    {
        // the rules must be returned in the correct (and following) order
        // since some rules may depend on their predecessors
        $validationRules = [
            new AnyOperationDefined(),
            new FragmentsMustNotFormCycles(),
            new OperationNameUniqueness(),
            new LoneAnonymousOperation(),
            new FieldSelectionsOnObjectsInterfacesAndUnionTypes_FragmentSpreadTypeExistence(),
            new FieldSelectionMerging(),
            new LeafFieldSelections(),
            new ArgumentName(),
            new ArgumentUniqueness(),
            new FragmentNameUniqueness(),
            new FragmentsOnCompositeTypes(),
            new FragmentsMustBeUsed(),
            new ValuesOfCorrectType(),
            new InputObjectFieldNames(),
            new InputObjectFieldUniqueness(),
            new InputObjectRequiredFields(),
            new DirectivesAreDefined(),
            new DirectivesAreUniquePerLocation(),
            new VariableUniqueness(),
            new VariablesAreInputTypes(),
            new AllVariableUsesDefined(),
            new AllVariableUsagesAreAllowed(),
            new AllVariablesUsed(),
            new RequiredArguments()
        ];

        // add aditional validation rules
        $validationRules = array_merge($validationRules, $this->additionalValidationRules);

        return $validationRules;
    }
}

?>