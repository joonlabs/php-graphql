<?php

namespace GraphQL\Schemas;

use GraphQL\Errors\GraphQLError;
use GraphQL\Types\GraphQLAbstractType;
use GraphQL\Types\GraphQLObjectType;
use GraphQL\Types\GraphQLType;

class Schema
{
    private $queryType;
    private $mutationType;
    private $directives;
    private $typeMap;
    private $subTypeMap;
    private $implementationsMap;

    /**
     * Schema constructor.
     * @param GraphQLObjectType $queryType
     * @param GraphQLObjectType $mutationType
     */
    public function __construct(?GraphQLObjectType $queryType, ?GraphQLObjectType $mutationType, ?array $directives = null)
    {
        $this->queryType = $queryType;
        $this->mutationType = $mutationType;
        $this->directives = $directives ?? [];

        $allReferencedTypes = [];
        $this->typeMap = [];
        $this->subTypeMap = [];

        // collect types from schema definnitions
        if ($queryType !== null) {
            $this->collectReferencedTypes($this->queryType, $allReferencedTypes);
        }
        if ($mutationType !== null) {
            $this->collectReferencedTypes($this->mutationType, $allReferencedTypes);
        }

        // build type map
        foreach ($allReferencedTypes as $namedType) {
            if ($namedType === null) {
                continue;
            }

            $typeName = $namedType->getName();
            if ($typeName === null or $typeName === "") {
                throw new GraphQLError("One of the provided types for building the Schema is missing a name.");
            }
            if (array_key_exists($typeName, $this->typeMap)) {
                throw new GraphQLError("Schema must contain uniquely named types but contains multiple types named \"$typeName\".");
            }

            $this->typeMap[$typeName] = $namedType;
        }

        //TODO: $this->implementationsMap (see: https://github.com/graphql/graphql-js/blob/5ed55b89d526c637eeb9c440715367eec8a2adec/src/type/schema.js#L223)
        $this->implementationsMap = [];
    }

    private function collectReferencedTypes(GraphQLType $type, array &$typeSet)
    {
        $namedType = $type->getNamedType();
        if (!in_array($namedType, $typeSet)) {
            $typeSet[] = $namedType;
            if ($namedType->isUnionType()) {
                // collect all union type members
                foreach ($namedType->getTypes() as $memberType) {
                    $this->collectReferencedTypes($memberType, $typeSet);
                }
            } else if ($namedType->isObjectType()) {
                // collect all object type interfaces, fields and args of fields
                foreach ($namedType->getInterfaces() as $interfaceType) {
                    $this->collectReferencedTypes($interfaceType, $typeSet);
                }

                foreach ($namedType->getFields() as $field) {
                    $this->collectReferencedTypes($field->getType(), $typeSet);
                    foreach ($field->getArguments() as $arg) {
                        $this->collectReferencedTypes($arg->getType(), $typeSet);
                    }
                }
            } else if ($namedType->isInputObjectType()) {
                foreach ($namedType->getFields() as $field) {
                    $this->collectReferencedTypes($field->getType(), $typeSet);
                }
            }
        }

        return $typeSet;
    }

    public function getPossibleTypes(GraphQLType $abstractType)
    {
        return $abstractType->isUnionType() ? $abstractType->getTypes() : $this->getImplementations($abstractType)["objects"];
    }

    public function getImplementations(GraphQLType $interfaceType)
    {
        $implementations = $this->implementationsMap[$interfaceType->getName()];
        return $implementations ?? ["objects" => [], "interfaces" => []];
    }

    public function getType(string $name): ?GraphQLType
    {
        return $this->typeMap[$name] ?? null;
    }

    public function isSubType(GraphQLAbstractType $abstractType, GraphQLType $maybeSubType)
    {
        $map = $this->subTypeMap[$abstractType->getName()];
        if($map === null) {
            $map = [];
            if ($abstractType->isUnionType()) {
                foreach ($abstractType->getTypes() as $type) {
                    $map[$type->getName()] = true;
                }
            } else {
                // must be implementationType
                $implementations = $this->getImplementations($abstractType);
                foreach ($implementations["objects"] as $type) {
                    $map[$type->getName()] = true;
                }
                foreach ($implementations["interfaces"] as $type) {
                    $map[$type->getName()] = true;
                }
            }
            $this->subTypeMap[$abstractType->getName()] = $map;
        }
        return $this->subTypeMap[$abstractType->getName()] !== null;
    }

    /**
     * @return GraphQLObjectType|null
     */
    public function getQueryType(): ?GraphQLObjectType
    {
        return $this->queryType;
    }

    /**
     * @param GraphQLObjectType|null $queryType
     * @return Schema
     */
    public function setQueryType(?GraphQLObjectType $queryType): Schema
    {
        $this->queryType = $queryType;
        return $this;
    }

    /**
     * @return GraphQLObjectType|null
     */
    public function getMutationType(): ?GraphQLObjectType
    {
        return $this->mutationType;
    }

    /**
     * @param GraphQLObjectType|null $mutationType
     * @return Schema
     */
    public function setMutationType(?GraphQLObjectType $mutationType): Schema
    {
        $this->mutationType = $mutationType;
        return $this;
    }
}

?>