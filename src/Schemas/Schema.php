<?php

namespace GraphQL\Schemas;

use GraphQL\Directives\GraphQLDirective;
use GraphQL\Directives\GraphQLIncludeDirective;
use GraphQL\Directives\GraphQLSkipDirective;
use GraphQL\Errors\GraphQLError;
use GraphQL\Types\GraphQLAbstractType;
use GraphQL\Types\GraphQLObjectType;
use GraphQL\Types\GraphQLType;
use GraphQL\Introspection\Introspection;

/**
 * Class Schema
 * @package GraphQL\Schemas
 */
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
     * @throws GraphQLError
     */
    public function __construct(?GraphQLObjectType $queryType, ?GraphQLObjectType $mutationType = null, ?array $directives = null)
    {
        $this->queryType = $queryType;
        $this->mutationType = $mutationType ?? null;
        $this->directives = array_merge([
            new GraphQLSkipDirective(),
            new GraphQLIncludeDirective()
        ], $directives ?? []);

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

        // collect types from directives
        foreach ($this->directives as $directive) {
            if ($directive instanceof GraphQLDirective) {
                foreach ($directive->getArguments() as $argument) {
                    $this->collectReferencedTypes($argument->getType(), $allReferencedTypes);
                }
            }
        }

        $__Schema = Introspection::buildIntrospectionSchemaParts()["__Schema"];
        $this->collectReferencedTypes($__Schema, $allReferencedTypes);

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

            // generate $this->implementationsMap
            if ($namedType->isInterfaceType()) {
                foreach ($namedType->getInterfaces() as $iface) {
                    if ($iface->isInterfaceType()) {
                        $implementations = $this->implementationsMap[$iface->getName()] ?? null;
                        if ($implementations === null) {
                            $this->implementationsMap[$iface->getName()] = [
                                "objects" => [],
                                "interfaces" => []
                            ];
                        }
                        $this->implementationsMap[$iface->getName()]["interfaces"][] = $namedType;
                    }
                }
            } else if ($namedType->isObjectType()) {
                foreach ($namedType->getInterfaces() as $iface) {
                    if ($iface->isInterfaceType()) {
                        $implementations = $this->implementationsMap[$iface->getName()] ?? null;
                        if ($implementations === null) {
                            $this->implementationsMap[$iface->getName()] = [
                                "objects" => [],
                                "interfaces" => []
                            ];
                        }
                        $this->implementationsMap[$iface->getName()]["objects"][] = $namedType;
                    }
                }
            }
        }
    }

    /**
     * @param GraphQLType $type
     * @param array $typeSet
     * @return array
     */
    private function collectReferencedTypes(GraphQLType $type, array &$typeSet): array
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

    /**
     * @param GraphQLType $abstractType
     * @return array|mixed
     */
    public function getPossibleTypes(GraphQLType $abstractType)
    {
        return $abstractType->isUnionType() ? $abstractType->getTypes() : $this->getImplementations($abstractType)["objects"];
    }

    /**
     * @param GraphQLType $interfaceType
     * @return array[]|mixed
     */
    public function getImplementations(GraphQLType $interfaceType)
    {
        $implementations = $this->implementationsMap[$interfaceType->getName()];
        return $implementations ?? ["objects" => [], "interfaces" => []];
    }

    /**
     * @param string $name
     * @return GraphQLType|null
     */
    public function getType(string $name): ?GraphQLType
    {
        return $this->typeMap[$name] ?? null;
    }

    /**
     * @param GraphQLAbstractType $abstractType
     * @param GraphQLType $maybeSubType
     * @return bool
     */
    public function isSubType(GraphQLAbstractType $abstractType, GraphQLType $maybeSubType): bool
    {
        $map = $this->subTypeMap[$abstractType->getName()] ?? null;
        if ($map === null) {
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

    /**
     * @return array
     */
    public function getTypeMap(): array
    {
        return $this->typeMap;
    }

    /**
     * @return array
     */
    public function getDirectives(): array
    {
        return $this->directives;
    }
}

