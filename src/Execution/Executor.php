<?php

namespace GraphQL\Execution;


use Closure;
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
use GraphQL\Utilities\Errors;
use GraphQL\Utilities\LocatedError;
use GraphQL\Utilities\OperationRootType;
use GraphQL\Introspection\Introspection;

/**
 * Class Executor
 * @package GraphQL\Execution
 */
class Executor
{
    /**
     * Executes a query against a document.
     *
     * @param Schema $schema
     * @param array $document
     * @param null $rootValue
     * @param null $contextValue
     * @param null $variableValues
     * @param null $operationName
     * @param null $fieldResolver
     * @param null $typeResolver
     * @return array
     * @throws GraphQLError
     */
    public function execute(Schema $schema, array $document, $rootValue = null, $contextValue = null, $variableValues = null, $operationName = null, $fieldResolver = null, $typeResolver = null): array
    {
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

        // return early errors if execution context failed.
        if(is_array($executionContext)){
            return [
                "errors" => Errors::prettyPrintErrors($executionContext),
                "data" => []
            ];
        }

        $data = $this->executeOperation($executionContext, $executionContext->getOperation(), $rootValue);
        return $this->buildResponse($executionContext, $data);
    }

    /**
     * Executes an operation
     *
     * @param ExecutionContext $executionContext
     * @param $operation
     * @param $rootValue
     * @return mixed|null
     * @throws GraphQLError
     */
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

    /**
     * Collects all fields in a selection set
     *
     * @param ExecutionContext $executionContext
     * @param GraphQLObjectType $runtimeType
     * @param $selectionSet
     * @param $fields
     * @param array $visitedFragmentNames
     * @return array
     * @throws GraphQLError
     */
    public function collectFields(ExecutionContext $executionContext, GraphQLObjectType $runtimeType, $selectionSet, &$fields, array $visitedFragmentNames): array
    {
        foreach ($selectionSet["selections"] as $selection) {
            switch ($selection["kind"]) {
                case "Field":
                    // check field
                    if (!$this->shouldIncludeNode($executionContext, $selection)) {
                        break;
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
                        break;
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
                        break;
                    }
                    $visitedFragmentNames[$fragName] = true;
                    $fragment = $executionContext->getFragments()[$fragName] ?? null;
                    if ($fragment === null || !$this->doesFragmentConditionMatch($executionContext, $fragment, $runtimeType)) {
                        break;
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

    /**
     * Returns the key that should be used for the field's answer
     *
     * @param $node
     * @return string
     */
    private function getFieldEntryKey($node): string
    {
        return $node["alias"] ? $node["alias"]["value"] : $node["name"]["value"];
    }

    /**
     * Returns wether a fragment condition matches or not
     *
     * @param ExecutionContext $executionContext
     * @param $fragment
     * @param GraphQLObjectType $type
     * @return bool
     * @throws GraphQLError
     */
    private function doesFragmentConditionMatch(ExecutionContext $executionContext, $fragment, GraphQLObjectType $type): bool
    {
        $typeConditionNode = $fragment["typeCondition"] ?? null;
        if ($typeConditionNode === null) {
            return true;
        }

        $conditionalType = Ast::typeFromAst($executionContext->getSchema(), $typeConditionNode);

        if ($conditionalType->getName() === $type->getName()) {
            return true;
        }
        if ($conditionalType->isAbstractType()) {
            return $executionContext->getSchema()->isSubType($conditionalType, $type);
        }
        return false;
    }

    /**
     * Returns wether a node should be included, based on the built-in directives
     *
     * @param ExecutionContext $executionContext
     * @param $node
     * @return bool
     * @throws GraphQLError
     */
    private function shouldIncludeNode(ExecutionContext $executionContext, $node): bool
    {
        // skip check for directives if no directives wanted
        if(empty($node["directives"]))
            return true;

        // check if skip directive is active
        $skip = Values::getDirectiveValues(
            new GraphQLSkipDirective(),
            $node,
            $executionContext->getVariableValues()
        );

        if (($skip["if"] ?? null) === true) {
            return false;
        }

        // check if include directive is active
        $include = Values::getDirectiveValues(
            new GraphQLIncludeDirective(),
            $node,
            $executionContext->getVariableValues()
        );

        if (($include["if"] ?? null) === false) {
            return false;
        }

        // in any other case return true
        return true;
    }

    /**
     * Executes fields in a parent type's context
     *
     * @param ExecutionContext $executionContext
     * @param GraphQLObjectType $parentType
     * @param $sourceValue
     * @param $path
     * @param $fields
     * @return array
     * @throws GraphQLError
     */
    public function executeFields(ExecutionContext $executionContext, GraphQLObjectType $parentType, $sourceValue, $path, $fields)
    {
        $initial = [];
        return array_reduce(array_keys($fields), function ($results, $responseName) use ($executionContext, $parentType, $sourceValue, $path, $fields) {
            $fieldNodes = $fields[$responseName];
            $fieldPath = [$responseName, $parentType->getName(), $path];

            $result = $this->resolveField(
                $executionContext,
                $parentType,
                $sourceValue,
                $fieldNodes,
                $fieldPath
            );

            $results[$responseName] = $result;
            return $results;
        }, $initial);
    }

    /**
     * Resolves a field's value
     *
     * @param ExecutionContext $executionContext
     * @param GraphQLObjectType $parentType
     * @param $source
     * @param $fieldNodes
     * @param $path
     * @return array|array[]|mixed|null[]|\null[][]|null
     * @throws GraphQLError
     */
    public function resolveField(ExecutionContext $executionContext, GraphQLObjectType $parentType, $source, $fieldNodes, $path)
    {
        $fieldNode = $fieldNodes[0];
        $fieldName = $fieldNode["name"]["value"];


        $fieldDef = $this->getFieldDef($executionContext->getSchema(), $parentType, $fieldName);
        if ($fieldDef === null) {
            return null;
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

            $contextValue = &$executionContext->getContextValue();

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
            $error = LocatedError::from($error, $fieldNodes, $path);
            return $this->handleFieldError($error, $returnType, $executionContext);
        }
    }

    /**
     * Field error is added to internal error-stack if field was not non-nullable field
     *
     * @param GraphQLError $error
     * @param GraphQLType $returnType
     * @param ExecutionContext $executionContext
     * @return null
     * @throws GraphQLError
     */
    private function handleFieldError(GraphQLError $error, GraphQLType $returnType, ExecutionContext $executionContext)
    {
        if ($returnType->isNonNullType()) {
            throw $error;
        }

        $executionContext->pushError($error);
        return null;
    }

    /**
     * @param ExecutionContext $executionContext
     * @param GraphQLType $returnType
     * @param $fieldNodes
     * @param $info
     * @param $path
     * @param $result
     * @return array|array[]|mixed|null[]|\null[][]|null
     * @throws GraphQLError
     */
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
                    "Cannot use [null] for non-nullable field {$info["parentType"]->getName()}.{$info["fieldName"]}."
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

    /**
     * @param ExecutionContext $executionContext
     * @param GraphQLList $returnType
     * @param $fieldNodes
     * @param $info
     * @param $path
     * @param $result
     * @return array|array[]|\array[][]|null[]|\null[][]|\null[][][]
     * @throws GraphQLError
     */
    private function completeListValue(ExecutionContext $executionContext, GraphQLList $returnType, $fieldNodes, $info, $path, $result): array
    {
        if (!is_iterable($result)) {
            throw new GraphQLError(
                "Expected Iterable, but did not find one for field \"{$info["parentType"]->getName()}.{$info["fieldName"]}\"."
            );
        }

        $itemType = $returnType->getInnerType();
        return array_map(function ($item, $index) use ($executionContext, $returnType, $fieldNodes, $info, $path, $result, $itemType) {
            $itemPath = [$index, null, $path];
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
                $error = LocatedError::from($error, $fieldNodes, $path);
                return $this->handleFieldError($error, $returnType, $executionContext);
            }
        }, $result, array_keys($result));
    }

    private function completeLeafValue(GraphQLType $returnType, $result)
    {
        return $returnType->serialize($result);
    }

    /**
     * @param ExecutionContext $executionContext
     * @param GraphQLAbstractType $returnType
     * @param $fieldNodes
     * @param $info
     * @param $path
     * @param $result
     * @return mixed
     * @throws GraphQLError
     */
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

    /**
     * @param $runtimeTypeName
     * @param ExecutionContext $executionContext
     * @param $returnType
     * @param $fieldNodes
     * @param $info
     * @param $result
     * @return GraphQLType
     * @throws GraphQLError
     */
    private function ensureValidRuntimeType($runtimeTypeName, ExecutionContext $executionContext, $returnType, $fieldNodes, $info, $result): GraphQLType
    {
        if ($runtimeTypeName === null) {
            throw new GraphQLError(
                "Abstract type \"{$returnType->getName()}\" must resolve to an Object type at runtime for field \"{$info["parentType"]->getName()}.{$info["fieldName"]}\". Either the \"{$returnType->getName()}\" type should provide a \"resolveType\" function or each possible type should provide an \"isTypeOf\" function.",
                $fieldNodes[0]
            );
        }

        if (!is_string($runtimeTypeName)) {
            throw new GraphQLError(
                "Abstract type \"{$returnType->getName()}\" must resolve to an Object type at runtime for field \"{$info["parentType"]->getName()}.{$info["fieldName"]}\" with value $result, received \"$runtimeTypeName\".",
                $fieldNodes[0]
            );
        }

        $runtimeType = $executionContext->getSchema()->getType($runtimeTypeName);
        if ($runtimeType === null) {
            throw new GraphQLError(
                "Abstract type \"{$returnType->getName()}\" was resolve to a type \"$runtimeTypeName\" that does not exist inside schema.",
                $fieldNodes[0]
            );
        }

        if (!$runtimeType->isObjectType()) {
            throw new GraphQLError(
                "Abstract type \"{$returnType->getName()}\" was resolve to a non-object type \"$runtimeTypeName\".",
                $fieldNodes[0]
            );
        }

        if (!$executionContext->getSchema()->isSubType($returnType, $runtimeType)) {
            throw new GraphQLError(
                "Runtime Object type \"{$runtimeType->getName()}\" is not a possible type for \"{$returnType->getName()}\".",
                $fieldNodes[0]
            );
        }

        return $runtimeType;
    }

    /**
     * @param ExecutionContext $executionContext
     * @param GraphQLObjectType $returnType
     * @param $fieldNodes
     * @param $info
     * @param $path
     * @param $result
     * @return mixed
     * @throws GraphQLError
     */
    private function completeObjectValue(ExecutionContext $executionContext, GraphQLObjectType $returnType, $fieldNodes, $info, $path, $result)
    {
        return $this->collectAndExecuteSubfields(
            $executionContext,
            $returnType,
            $fieldNodes,
            $info,
            $path,
            $result
        );
    }

    /**
     * @param ExecutionContext $executionContext
     * @param GraphQLObjectType $returnType
     * @param $fieldNodes
     * @param $info
     * @param $path
     * @param $result
     * @return mixed
     * @throws GraphQLError
     */
    private function collectAndExecuteSubfields(ExecutionContext $executionContext, GraphQLObjectType $returnType, $fieldNodes, $info, $path, $result)
    {
        $subFieldNodes = $this->collectSubFields($executionContext, $returnType, $fieldNodes);
        return $this->executeFields($executionContext, $returnType, $result, $path, $subFieldNodes);
    }

    /**
     * @param ExecutionContext $executionContext
     * @param GraphQLObjectType $returnType
     * @param $fieldNodes
     * @return array
     * @throws GraphQLError
     */
    private function collectSubFields(ExecutionContext $executionContext, GraphQLObjectType $returnType, $fieldNodes): array
    {
        $subFieldNodes = [];
        $visitedFragmentNames = [];
        foreach ($fieldNodes as $node) {
            if ($node["selectionSet"]) {
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

    /**
     * @param ExecutionContext $executionContext
     * @param GraphQLTypeField $fieldDef
     * @param $fieldNodes
     * @param $parentType
     * @param $path
     * @return array
     */
    private function buildResolveInfo(ExecutionContext $executionContext, GraphQLTypeField $fieldDef, $fieldNodes, $parentType, $path): array
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

    /**
     * @param Schema $schema
     * @param GraphQLObjectType $parentType
     * @param string $fieldName
     * @return mixed|null
     * @throws \GraphQL\Errors\BadImplementationError
     */
    private function getFieldDef(Schema $schema, GraphQLObjectType $parentType, string $fieldName)
    {
        if ($fieldName === "__schema" && $schema->getQueryType() === $parentType) {
            return Introspection::getSchemaMetaFieldDef();
        } else if ($fieldName === "__type" && $schema->getQueryType() === $parentType) {
            return Introspection::getTypeMetaFieldDef();
        } else if ($fieldName === "__typename") {
            return Introspection::getTypeNameMetaFieldDef();
        }
        return $parentType->getFields()[$fieldName];
    }

    /**
     * @param Schema $schema
     * @param array $document
     * @param null $rootValue
     * @param null $contextValue
     * @param null $rawVariableValues
     * @param null $operationName
     * @param null $fieldResolver
     * @param null $typeResolver
     * @return GraphQLError[]|ExecutionContext
     */
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
                    new GraphQLError("Unknown operation named \"$operationName\"", $definition["loc"] ?? null)
                ];
            }
            return [
                new GraphQLError("Must provide an operation.", $definition["loc"] ?? null)
            ];
        }

        $variableDefinitions = $operation["variableDefinitions"] ?? [];
        $coercedVariableValues = Values::getVariableValues($schema, $variableDefinitions, $rawVariableValues ?? []);

        // default $contextValue to an empty array
        $contextValue = $contextValue ?? [];

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

    /**
     * @param ExecutionContext $executionContext
     * @param $data
     * @return array
     */
    private function buildResponse(ExecutionContext $executionContext, $data): array
    {
        if (count($executionContext->getErrors()) === 0) {
            return [
                "data" => $data
            ];
        } else {
            return [
                "errors" => Errors::prettyPrintErrors($executionContext->getErrors()),
                "data" => $data
            ];
        }
    }

    /**
     * @return Closure
     */
    private function getDefaultFieldResolver(): Closure
    {
        return function ($source, $args, $contextValue, $info) {
            // if $source is an iterable (e.g. array), get value by key (field name)
            if (is_iterable($source)) {
                return $source[$info["fieldName"]] ?? null;
            }
            // if $source is an object, get value by either calling the getter or trying to acces property directly
            if (is_object($source)) {
                $propertyName = $info["fieldName"];
                $methodName = "get" . ucwords($info["fieldName"]);
                if (method_exists($source, $methodName)) {
                    return $source->{$methodName}();
                }
                if (property_exists($source, $propertyName)) {
                    return $source->{$propertyName};
                }
                return null;
            }
            return null;
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
    private function getDefaultTypeResolver(): Closure
    {
        return function ($value, $contextValue, $info, $abstractType) {
            if (is_iterable($value) and is_string($value["__typename"] ?? null)) {
                return $value["__typename"];
            }

            $possibleTypes = $info["schema"]->getPossibleTypes($abstractType);

            // sort possibleTypes by the amount of fields they provide (ascending)
            // this helps the to prevent fetching the wrong type, because a subset of the fields
            // match with the required fields.
            usort($possibleTypes, function ($a, $b) {
                $countFieldsA = count($a->getFields());
                $countFieldsB = count($b->getFields());
                if ($countFieldsA === $countFieldsB) {
                    return 0;
                } else if ($countFieldsA > $countFieldsB) {
                    return 1;
                } else {
                    return -1;
                }
            });

            for ($i = 0; $i < count($possibleTypes); $i++) {
                $type = $possibleTypes[$i];
                if (method_exists($type, "isTypeOf")) {
                    $isTypeOfResult = $type->isTypeOf($value, $contextValue, $info);
                    if ($isTypeOfResult) {
                        return $type->getName();
                    }
                }
            }

            return null;
        };
    }

}
