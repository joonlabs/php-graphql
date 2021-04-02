<?php

namespace GraphQL\Execution;


use Cassandra\FutureRows;
use GraphQL\Arguments\GraphQLFieldArgument;
use GraphQL\Directives\GraphQLIncludeDirective;
use GraphQL\Directives\GraphQLSkipDirective;
use GraphQL\Errors\GraphQLError;
use GraphQL\Fields\GraphQLTypeField;
use GraphQL\Schemas\Schema;
use GraphQL\Types\GraphQLAbstractType;
use GraphQL\Types\GraphQLList;
use GraphQL\Types\GraphQLObjectType;
use GraphQL\Types\GraphQLType;
use GraphQL\Utilities\Ast;
use GraphQL\Utilities\LocatedError;
use GraphQL\Utilities\OperationRootType;

class Executor
{
    public function execute(Schema $schema, array $document, $rootValue = null, $contextValue = null, $variableValues = null, $operationName = null, $fieldResolver = null, $typeResolver = null)
    {
        //TODO: Validate

        // build execution context
        $executionContext = $this->buildExecutionContext(
            $schema,
            $document,
            $rootValue,
            $contextValue,
            $variableValues,
            $operationName,
            $fieldResolver,
            $typeResolver
        );

        $data = $this->executeOperation($executionContext, $executionContext->getOperation(), $rootValue);
        return $this->buildResponse($executionContext, $data);
    }

    public function executeOperation(ExecutionContext $executionContext, $operation, $rootValue)
    {
        $type = OperationRootType::getOperationRootType($executionContext->getSchema(), $operation);
        $fields = [];
        $fields = $this->collectFields(
            $executionContext,
            $type,
            $operation["selectionSet"],
            $fields,
            []
        );
        $path = null;
        try {
            return $this->executeFields($executionContext, $type, $rootValue, $path, $fields);
        } catch (GraphQLError $error) {
            $executionContext->pushError($error);
            return null;
        }
    }

    public function collectFields(ExecutionContext $executionContext, GraphQLObjectType $runtimeType, $selectionSet, &$fields, array $visitedFragmentNames)
    {
        foreach ($selectionSet["selections"] as $selection) {
            switch ($selection["kind"]) {
                case "Field":
                    // check field
                    if (!$this->shouldIncludeNode($executionContext, $selection)) {
                        continue;
                    }
                    $name = $this->getFieldEntryKey($selection);
                    if (!($fields[$name] ?? null)) {
                        $fields[$name] = [];
                    }
                    $fields[$name][] = $selection;
                    break;
                case "InlineFragment":
                    if (!$this->shouldIncludeNode($executionContext, $selection)
                        || !$this->doesFragmentConditionMatch($executionContext, $selection, $runtimeType)) {
                        continue;
                    }
                    $this->collectFields(
                        $executionContext,
                        $runtimeType,
                        $selection["selectionSet"],
                        $fields,
                        $visitedFragmentNames
                    );
                    break;
                case "FragmentSpread":
                    $fragName = $selection["name"]["value"];
                    if (array_key_exists($fragName, $visitedFragmentNames) || !$this->shouldIncludeNode($executionContext, $selection)) {
                        continue;
                    }
                    $visitedFragmentNames[$fragName] = true;
                    $fragment = $executionContext->getFragments()[$fragName];
                    if (!$fragment || !$this->doesFragmentConditionMatch($executionContext, $fragment, $runtimeType)) {
                        continue;
                    }
                    $this->collectFields(
                        $executionContext,
                        $runtimeType,
                        $fragment["selectionSet"],
                        $fields,
                        $visitedFragmentNames
                    );
                    break;
            }
        }
        return $fields;
    }

    private function getFieldEntryKey($node): string
    {
        return $node["alias"] ? $node["alias"]["value"] : $node["name"]["value"];
    }

    private function doesFragmentConditionMatch(ExecutionContext $executionContext, $fragment, GraphQLObjectType $type)
    {
        $typeConditionNode = $fragment["typeCondition"];
        if (!$typeConditionNode) {
            return true;
        }
        $conditionalType = Ast::typeFromAst($executionContext->getSchema(), $typeConditionNode);
        // TODO: check if object are really equal!? maybe a->getName() === $b->getName()???
        if ($conditionalType === $type) {
            return true;
        }
        if ($conditionalType->isAbstractType()) {
            return $executionContext->getSchema()->isSubType($conditionalType, $type);
        }
        return false;
    }

    private function shouldIncludeNode(ExecutionContext $executionContext, $node)
    {
        // check if skip directive is active
        $skip = Values::getDirectiveValues(
            new GraphQLSkipDirective(),
            $node,
            $executionContext->getVariableValues()
        );

        if ($skip["if"] === true) {
            return false;
        }

        // check if include directive is active
        $include = Values::getDirectiveValues(
            new GraphQLIncludeDirective(),
            $node,
            $executionContext->getVariableValues()
        );

        if ($include["if"] === false) {
            return false;
        }

        // in any other case return true
        return true;
    }

    public function executeFields(ExecutionContext $executionContext, GraphQLObjectType $parentType, $sourceValue, $path, $fields)
    {
        return array_reduce(array_keys($fields), function (&$results, $responseName) use ($executionContext, $parentType, $sourceValue, $path, $fields) {
            $fieldNodes = $fields[$responseName];
            $fieldPath = [$path, $responseName, $parentType->getName()];
            $result = $this->resolveField(
                $executionContext,
                $parentType,
                $sourceValue,
                $fieldNodes,
                $fieldPath
            );
            if($result === null){
                return $results;
            }

            $results[$responseName] = $result;
            return $results;
        }, []);
    }

    public function resolveField(ExecutionContext $executionContext, GraphQLObjectType $parentType, $source, $fieldNodes, $path)
    {
        $fieldNode = $fieldNodes[0];
        $fieldName = $fieldNode["name"]["value"];

        $fieldDef = $this->getFieldDef($executionContext->getSchema(), $parentType, $fieldName);
        if (!$fieldDef) {
            return;
        }

        $returnType = $fieldDef->getType();
        $resolveFn = $fieldDef->getResolve() ?? $executionContext->getFieldResolver();

        $info = $this->buildResolveInfo(
            $executionContext,
            $fieldDef,
            $fieldNodes,
            $parentType,
            $path
        );

        try {
            $args = Values::getArgumentValues($fieldDef, $fieldNodes[0], $executionContext->getVariableValues());

            $contextValue = $executionContext->getContextValue();

            $result = $resolveFn($source, $args, $contextValue, $info);

            return $this->completeValue(
                $executionContext,
                $returnType,
                $fieldNodes,
                $info,
                $path,
                $result
            );
        } catch (GraphQLError $error) {
            // TODO (see: https://github.com/graphql/graphql-js/blob/5ed55b89d526c637eeb9c440715367eec8a2adec/src/execution/execute.js#L654)
            $error = LocatedError::from($error, $fieldNodes, $path);
            return $this->handleFieldError($error, $returnType, $executionContext);
        }
    }

    private function handleFieldError(GraphQLError $error, GraphQLType $returnType, ExecutionContext $executionContext){
        if($returnType->isNonNullType()){
            throw $error;
        }

        $executionContext->pushError($error);
        return null;
    }

    function completeValue(ExecutionContext $executionContext, GraphQLType $returnType, $fieldNodes, $info, $path, $result)
    {
        // if result is error, throw a located error
        if ($result instanceof GraphQLError) {
            throw $result;
        }

        // If field type is NonNull, complete for inner type, and throw field error, if result is null.
        if ($returnType->isNonNullType()) {
            $completed = $this->completeValue(
                $executionContext,
                $returnType->getInnerType(),
                $fieldNodes,
                $info,
                $path,
                $result
            );
            if ($completed === null) {
                throw new GraphQLError(
                    "Cannot return null for non-nullable field {$info["parentType"]->getName()}.{$info["fieldName"]}."
                );
            }
            return $completed;
        }

        // If result value is null or undefined then return null.
        if ($result === null) {
            return null;
        }

        // If field type is List, complete each item in the list with the inner type
        if ($returnType->isListType()) {
            return $this->completeListValue(
                $executionContext,
                $returnType,
                $fieldNodes,
                $info,
                $path,
                $result
            );
        }

        // If field type is a leaf type, Scalar or Enum, serialize to a valid value,
        // returning null if serialization is not possible.
        if ($returnType->isLeafType()) {
            return $this->completeLeafValue($returnType, $result);
        }

        // If field type is an abstract type, Interface or Union, determine the
        // runtime Object type and complete for that type.
        if ($returnType->isAbstractType()) {
            return $this->completeAbstractValue(
                $executionContext,
                $returnType,
                $fieldNodes,
                $info,
                $path,
                $result
            );
        }

        // If field type is Object, execute and complete all sub-selections.
        if ($returnType->isObjectType()) {
            return $this->completeObjectValue(
                $executionContext,
                $returnType,
                $fieldNodes,
                $info,
                $path,
                $result
            );
        }

        throw new GraphQLError(
            'Cannot complete value of unexpected and unknown output type: '
        );
    }

    private function completeListValue(ExecutionContext $executionContext, GraphQLList $returnType, $fieldNodes, $info, $path, $result)
    {
        if (!is_iterable($result)) {
            throw new GraphQLError(
                "Expected Iterable, but did not find one for field \"{$info["parentType"]->getName()}.{$info["fieldName"]}\"."
            );
        }

        $itemType = $returnType->getInnerType();
        $completedResults = array_map(function ($item, $index) use ($executionContext, $returnType, $fieldNodes, $info, $path, $result, $itemType) {
            $itemPath = [$path, $index, null];
            try {
                return $this->completeValue(
                    $executionContext,
                    $itemType,
                    $fieldNodes,
                    $info,
                    $itemPath,
                    $item
                );
            } catch (GraphQLError $error) {
                // TODO (see: https://github.com/graphql/graphql-js/blob/5ed55b89d526c637eeb9c440715367eec8a2adec/src/execution/execute.js#L877)
                echo "THIS IS A FIELD ERROR";
                exit();
            }
        }, $result, array_keys($result));
        return $completedResults;
    }

    private function completeLeafValue(GraphQLType $returnType, $result)
    {
        $serializedResult = $returnType->serialize($result);
        return $serializedResult;
    }

    private function completeAbstractValue(ExecutionContext $executionContext, GraphQLAbstractType $returnType, $fieldNodes, $info, $path, $result)
    {
        $resolveTypeFn = $returnType->getResolveType() ?? $executionContext->getTypeResolver();
        $contextValue = $executionContext->getContextValue();
        $runtimeType = $resolveTypeFn($result, $contextValue, $info, $returnType);

        return $this->completeObjectValue(
            $executionContext,
            $this->ensureValidRuntimeType(
                $runtimeType,
                $executionContext,
                $returnType,
                $fieldNodes,
                $info,
                $result
            ),
            $fieldNodes,
            $info,
            $path,
            $result
        );
    }

    private function ensureValidRuntimeType($runtimeTypeName, ExecutionContext $executionContext, $returnType, $fieldNodes, $info, $result)
    {
        if ($runtimeTypeName === null) {
            throw new GraphQLError(
                "Abstract type \"{$returnType->getName()}\" must resolve to an Object type at runtime for field \"{$info["parentType"]->getName()}.{$info["fieldName"]}\". Either the \"{$returnType->getName()}\" type should provide a \"resolveType\" function or each possible type should provide an \"isTypeOf\" function."
            );
        }

        if (!is_string($runtimeTypeName)) {
            throw new GraphQLError(
                "Abstract type \"{$returnType->getName()}\" must resolve to an Object type at runtime for field \"{$info["parentType"]->getName()}.{$info["fieldName"]}\" with value {$result}, received \"{$runtimeTypeName}\"."
            );
        }

        $runtimeType = $executionContext->getSchema()->getType($runtimeTypeName);
        if ($runtimeType === null) {
            throw new GraphQLError(
                "Abstract type \"{$returnType->getName()}\" was resolve to a type \"{$runtimeTypeName}\" that does not exist inside schema."
            );
        }

        if (!$runtimeType->isObjectType()) {
            throw new GraphQLError(
                "Abstract type \"{$returnType->getName()}\" was resolve to a non-object type \"{$runtimeTypeName}\"."
            );
        }

        if (!$executionContext->getSchema()->isSubType($returnType, $runtimeType)) {
            throw new GraphQLError(
                "Runtime Object type \"{$runtimeType->getName()}\" is not a possible type for \"{$returnType->getName()}\"."
            );
        }

        return $runtimeType;
    }

    private function completeObjectValue(ExecutionContext $executionContext, GraphQLObjectType $returnType, $fieldNodes, $info, $path, $result)
    {
        return $this->collectAndExecuteSubfields(
            $executionContext,
            $returnType,
            $fieldNodes,
            $path,
            $result
        );
    }

    private function collectAndExecuteSubfields(ExecutionContext $executionContext, GraphQLObjectType $returnType, $fieldNodes, $path, $result)
    {
        $subFieldNodes = $this->collectSubFields($executionContext, $returnType, $fieldNodes);
        return $this->executeFields($executionContext, $returnType, $result, $path, $subFieldNodes);
    }

    private function collectSubFields(ExecutionContext $executionContext, GraphQLObjectType $returnType, $fieldNodes)
    {
        $subFieldNodes = [];
        $visitedFragmentNames = [];
        foreach ($fieldNodes as $node)
        {
            if($node["selectionSet"]){
                $subFieldNodes = $this->collectFields(
                    $executionContext,
                    $returnType,
                    $node["selectionSet"],
                    $subFieldNodes,
                    $visitedFragmentNames
                );
            }
        }
        return $subFieldNodes;
    }

    private function buildResolveInfo(ExecutionContext $executionContext, GraphQLTypeField $fieldDef, $fieldNodes, $parentType, $path)
    {
        return [
            "fieldName" => $fieldDef->getName(),
            "fieldNodes" => $fieldNodes,
            "returnType" => $fieldDef->getType(),
            "parentType" => $parentType,
            "path" => $path,
            "schema" => $executionContext->getSchema(),
            "fragments" => $executionContext->getFragments(),
            "rootValue" => $executionContext->getRootValue(),
            "operation" => $executionContext->getOperation(),
            "variableValues" => $executionContext->getVariableValues(),
        ];
    }

    private function getFieldDef(Schema $schema, GraphQLObjectType $parentType, string $fieldName)
    {
        if ($fieldName === "__schema" && $schema->getQueryType() === $parentType) {
            return null; // TODO (see: https://github.com/graphql/graphql-js/blob/5ed55b89d526c637eeb9c440715367eec8a2adec/src/execution/execute.js#L1193):
        } else if ($fieldName === "__type" && $schema->getQueryType() === $parentType) {
            return null; // TODO (see: https://github.com/graphql/graphql-js/blob/5ed55b89d526c637eeb9c440715367eec8a2adec/src/execution/execute.js#L1200):
        } else if ($fieldName === "__typename" && $schema->getQueryType() === $parentType) {
            return null; // TODO (see: https://github.com/graphql/graphql-js/blob/5ed55b89d526c637eeb9c440715367eec8a2adec/src/execution/execute.js#L1202):
        }
        return $parentType->getFields()[$fieldName];
    }

    public function buildExecutionContext(Schema $schema, array $document, $rootValue = null, $contextValue = null, $rawVariableValues = null, $operationName = null, $fieldResolver = null, $typeResolver = null)
    {
        $operation = null;
        $fragments = [];

        // loop through the document and fetch fragments and the wanted (or first) operation
        foreach ($document["definitions"] as $definition) {
            switch ($definition["kind"]) {
                case "OperationDefinition":
                    if ($operationName === null) {
                        if ($operation !== null) {
                            return [
                                new GraphQLError("Must provide operation name if query contains multiple operations.", $definition["loc"])
                            ];
                        }
                        $operation = $definition;
                    } else if ($definition["name"]["value"] === $operationName) {
                        $operation = $definition;
                    }
                    break;
                case "FragmentDefinition":
                    $fragments[$definition["name"]["value"]] = $definition;
                    break;
            }
        }

        // check if operation is not selected and why
        if ($operation === null) {
            if ($operationName !== null) {
                return [
                    new GraphQLError("Unknown operation named \"$operationName\"", $definition["loc"])
                ];
            }
            return [
                new GraphQLError("Must provide an operation.", $definition["loc"])
            ];
        }

        $variableDefinitions = $operation["variableDefinitions"] ?? [];
        $coercedVariableValues = Values::getVariableValues($schema, $variableDefinitions, $rawVariableValues ?? []);

        return new ExecutionContext(
            $schema,
            $fragments,
            $rootValue,
            $contextValue,
            $operation,
            $coercedVariableValues,
            $fieldResolver ?? $this->getDefaultFieldResolver(),
            $typeResolver ?? $this->getDefaultTypeResolver(),
            []
        );
    }

    private function buildResponse(ExecutionContext $executionContext, $data)
    {
        if (count($executionContext->getErrors()) === 0) {
            return [
                "data" => $data
            ];
        } else {
            return [
                "errors" => $this->prettyPrintErrors($executionContext->getErrors()),
                "data" => $data
            ];
        }
    }

    private function prettyPrintErrors(array $errors)
    {
        return array_map(function(GraphQLError $error){
            return [
                "message" => $error->getMessage(),
                "locations" => $error->getLocations(),
                "path" => $error->getPath(),
                "extensions" => [
                    "code" => $error->getErrorCode()
                ]
            ];
        }, $errors);
    }

    private function getDefaultFieldResolver(): \Closure
    {
        return function ($source, $args, $contextValue, $info) {
            return $source[$info["fieldName"]];
            // TODO: implement full default resolver (see: https://github.com/graphql/graphql-js/blob/5ed55b89d526c637eeb9c440715367eec8a2adec/src/execution/execute.js#L1161)
        };
    }

    /**
     * If a resolveType function is not given, then a default resolve behavior is
     * used which attempts two strategies:
     *
     * First, See if the provided value has a `__typename` field defined, if so, use
     * that value as name of the resolved type.
     *
     * Otherwise, test each possible type for the abstract type by calling
     * isTypeOf for the object being coerced, returning the first type that matches.
     */
    private function getDefaultTypeResolver(): \Closure
    {
        return function ($value, $contextValue, $info, $abstractType) {
            if (is_iterable($value) and is_string($value["__typename"])) {
                return $value["__typename"];
            }

            $possibleTypes = $info["schema"]->getPossibleTypes($abstractType);

            for ($i = 0; $i < count($possibleTypes); $i++) {
                $type = $possibleTypes[$i];
                if (method_exists($type, "isTypeOf")) {
                    $isTypeOfResult = $type->isTypeOf($value, $contextValue, $info);
                    if ($isTypeOfResult) {
                        return $type->getName();
                    }
                }
            }
        };
    }

}

?>