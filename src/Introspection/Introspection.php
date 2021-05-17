<?php

namespace GraphQL\Introspection;

use GraphQL\Errors\BadImplementationError;
use GraphQL\Fields\GraphQLTypeField;
use GraphQL\Types\GraphQLObjectType;
use GraphQL\Types\GraphQLBoolean;
use GraphQL\Types\GraphQLList;
use GraphQL\Types\GraphQLNonNull;
use GraphQL\Types\GraphQLEnum;
use GraphQL\Types\GraphQLEnumValue;
use GraphQL\Types\GraphQLString;
use GraphQL\Types\GraphQLType;
use GraphQL\Schemas\Schema;
use GraphQL\Directives\GraphQLDirective;
use GraphQL\Arguments\GraphQLFieldArgument;

/**
 * Class Introspection
 * @package GraphQL\Introspection
 */
class Introspection{
    /**
     * @return array
     * @throws BadImplementationError
     */
    static public function buildIntrospectionSchemaParts() : array
    {
        $__Type = null;
        $__Field = null;
        $__TypeKind = null;
        $__Directive = null;
        $__EnumValue = null;
        $__InputValue = null;
        $__DirectiveLocation = null;

        $__Schema = new GraphQLObjectType(
            "__Schema",
            "A GraphQL Schema defines the capabilities of a GraphQL server. It exposes all available types and directives on the server, as well as the entry points for query and mutation operations.",
            function () use (&$__Type, &$__Directive) {
                return [
                    new GraphQLTypeField(
                        "description",
                        new GraphQLString()
                    ),
                    new GraphQLTypeField(
                        "types",
                        new GraphQLNonNull(new GraphQLList(new GraphQLNonNull($__Type))),
                        "A list of all types supported by this server.",
                        function (Schema $schema) {
                            return array_values($schema->getTypeMap());
                        }
                    ),
                    new GraphQLTypeField(
                        "queryType",
                        new GraphQLNonNull($__Type),
                        "The type that query operations will be rooted at.",
                        function (Schema $schema) {
                            return $schema->getQueryType();
                        }
                    ),
                    new GraphQLTypeField(
                        "mutationType",
                        $__Type,
                        "If this server supports mutation, the type that mutation operations will be rooted at.",
                        function (Schema $schema) {
                            return $schema->getMutationType();
                        }
                    ),
                    new GraphQLTypeField(
                        "subscriptionType",
                        $__Type,
                        "If this server support subscription, the type that subscription operations will be rooted at.",
                        function (Schema $schema) {
                            return null;
                        }
                    ),
                    new GraphQLTypeField(
                        "directives",
                        new GraphQLNonNull(new GraphQLList(new GraphQLNonNull($__Directive))),
                        "A list of all directives supported by this server.",
                        function (Schema $schema) {
                            return $schema->getDirectives();
                        }
                    )
                ];

            }
        );

        $__Directive = new GraphQLObjectType(
            "__Directive",
            "A Directive provides a way to describe alternate runtime execution and type validation behavior in a GraphQL document.\n\nIn some cases, you need to provide options to alter GraphQL's execution behavior in ways field arguments will not suffice, such as conditionally including or skipping a field. Directives provide this by describing additional information to the executor.",
            function () use (&$__DirectiveLocation, &$__InputValue) {
                return [
                    new GraphQLTypeField(
                        "name",
                        new GraphQLNonNull(new GraphQLString()),
                        "",
                        function (GraphQLDirective $directive) {
                            return $directive->getName();
                        }
                    ),
                    new GraphQLTypeField(
                        "description",
                        new GraphQLString(),
                        "",
                        function (GraphQLDirective $directive) {
                            return $directive->getDescription();
                        }
                    ),
                    new GraphQLTypeField(
                        "isRepeatable",
                        new GraphQLNonNull(new GraphQLBoolean()),
                        "",
                        function (GraphQLDirective $directive) {
                            return $directive->isRepetable();
                        }
                    ),
                    new GraphQLTypeField(
                        "locations",
                        new GraphQLNonNull(new GraphQLList(new GraphQLNonNull($__DirectiveLocation))),
                        "",
                        function (GraphQLDirective $directive) {
                            return $directive->getLocations();
                        }
                    ),
                    new GraphQLTypeField(
                        "args",
                        new GraphQLNonNull(new GraphQLList(new GraphQLNonNull($__InputValue))),
                        "",
                        function (GraphQLDirective $directive) {
                            return $directive->getArguments();
                        }
                    )
                ];
            }
        );

        $__DirectiveLocation = new GraphQLEnum(
            "__DirectiveLocation",
            "A Directive can be adjacent to many parts of the GraphQL language, a __DirectiveLocation describes one such possible adjacencies.",
            [
                new GraphQLEnumValue("QUERY", "Location adjacent to a query operation."),
                new GraphQLEnumValue("MUTATION", "Location adjacent to a mutation operation."),
                new GraphQLEnumValue("SUBSCRIPTION", "Location adjacent to a subscription operation."),
                new GraphQLEnumValue("FIELD", "Location adjacent to a field."),
                new GraphQLEnumValue("FRAGMENT_DEFINITION", "Location adjacent to a fragment definition."),
                new GraphQLEnumValue("FRAGMENT_SPREAD", "Location adjacent to a fragment spread."),
                new GraphQLEnumValue("INLINE_FRAGMENT", "Location adjacent to an inline fragment."),
                new GraphQLEnumValue("VARIABLE_DEFINITION", "Location adjacent to a variable definition."),
                new GraphQLEnumValue("SCHEMA", "Location adjacent to a schema definition."),
                new GraphQLEnumValue("SCALAR", "Location adjacent to a scalar definition."),
                new GraphQLEnumValue("OBJECT", "Location adjacent to an object type definition."),
                new GraphQLEnumValue("FIELD_DEFINITION", "Location adjacent to a field definition."),
                new GraphQLEnumValue("ARGUMENT_DEFINITION", "Location adjacent to an argument definition."),
                new GraphQLEnumValue("INTERFACE", "Location adjacent to an interface definition."),
                new GraphQLEnumValue("UNION", "Location adjacent to a union definition."),
                new GraphQLEnumValue("ENUM", "Location adjacent to an enum definition."),
                new GraphQLEnumValue("ENUM_VALUE", "Location adjacent to an enum value definition."),
                new GraphQLEnumValue("INPUT_OBJECT", "Location adjacent to an input object type definition."),
                new GraphQLEnumValue("INPUT_FIELD_DEFINITION", "Location adjacent to an input object field definition.")
            ]
        );

        $__Type = new GraphQLObjectType(
            "__Type",
            "The fundamental unit of any GraphQL Schema is the type. There are many kinds of types in GraphQL as represented by the `__TypeKind` enum.\n\nDepending on the kind of a type, certain fields describe information about that type. Scalar types provide no information beyond a name, description and optional `specifiedByUrl`, while Enum types provide their values. Object and Interface types provide the fields they describe. Abstract types, Union and Interface, provide the Object types possible at runtime. List and NonNull types compose other types.",
            function () use (&$__EnumValue, &$__InputValue, &$__TypeKind, &$__Field, &$__Type) {
                return [
                    new GraphQLTypeField(
                        "kind",
                        new GraphQLNonNull($__TypeKind),
                        "",
                        function (GraphQLType $type) {
                            if ($type->isScalarType()) return "SCALAR";
                            if ($type->isObjectType()) return "OBJECT";
                            if ($type->isInterfaceType()) return "INTERFACE";
                            if ($type->isUnionType()) return "UNION";
                            if ($type->isEnumType()) return "ENUM";
                            if ($type->isInputObjectType()) return "INPUT_OBJECT";
                            if ($type->isListType()) return "LIST";
                            if ($type->isNonNullType()) return "NON_NULL";
                            return null;
                        }
                    ),
                    new GraphQLTypeField(
                        "name",
                        new GraphQLString(),
                        "",
                        function (GraphQLType $type) {
                            return $type->getName();
                        }

                    ),
                    new GraphQLTypeField(
                        "description",
                        new GraphQLString(),
                        "",
                        function (GraphQLType $type) {
                            return $type->getDescription();
                        }

                    ),
                    new GraphQLTypeField(
                        "specifiedByUrl",
                        new GraphQLString()
                    ),
                    new GraphQLTypeField(
                        "fields",
                        new GraphQLList(new GraphQLNonNull($__Field)),
                        "",
                        function (GraphQLType $type, $args) {
                            if ($type->isObjectType() || $type->isInterfaceType()) {
                                $fields = array_values($type->getFields());
                                return $args["includeDeprecated"]
                                    ? $fields
                                    : array_filter($fields, function (GraphQLTypeField $field) {
                                        return $field->getDeprecationReason() === null;
                                    });
                            }
                            return null;
                        },
                        [
                            new GraphQLFieldArgument("includeDeprecated", new GraphQLBoolean(), "", false)
                        ]
                    ),
                    new GraphQLTypeField(
                        "interfaces",
                        new GraphQLList(new GraphQLNonNull($__Type)),
                        "",
                        function (GraphQLType $type) {
                            if ($type->isObjectType() || $type->isInterfaceType()) {
                                return $type->getInterfaces();
                            }
                            return null;
                        }
                    ),
                    new GraphQLTypeField(
                        "possibleTypes",
                        new GraphQLList(new GraphQLNonNull($__Type)),
                        "",
                        function (GraphQLType $type, $_, $__, $info) {
                            if ($type->isAbstractType()) {
                                return $info["schema"]->getPossibleTypes($type);
                            }
                            return null;
                        }
                    ),
                    new GraphQLTypeField(
                        "enumValues",
                        new GraphQLList(new GraphQLNonNull($__EnumValue)),
                        "",
                        function (GraphQLType $type, $args) {
                            if ($type->isEnumType()) {
                                $values = $type->getValues();
                                return $args["includeDeprecated"]
                                    ? $values
                                    : array_filter($values, function (GraphQLTypeField $field) {
                                        return $field->getDeprecationReason() === null;
                                    });
                            }
                            return null;
                        },
                        [
                            new GraphQLFieldArgument("includeDeprecated", new GraphQLBoolean(), "", false)
                        ]
                    ),
                    new GraphQLTypeField(
                        "inputFields",
                        new GraphQLList(new GraphQLNonNull($__InputValue)),
                        "",
                        function (GraphQLType $type, $args) {
                            if ($type->isInputObjectType()) {
                                $fields = array_values($type->getFields());
                                return $args["includeDeprecated"]
                                    ? $fields
                                    : array_filter($fields, function (GraphQLTypeField $field) {
                                        return $field->getDeprecationReason() === null;
                                    });
                            }
                            return null;
                        },
                        [
                            new GraphQLFieldArgument("includeDeprecated", new GraphQLBoolean(), "", false)
                        ]
                    ),
                    new GraphQLTypeField(
                        "ofType",
                        $__Type,
                        "",
                        function (GraphQLType $type) {
                            return $type->isWrappingType() ? $type->getInnerType() : null;
                        }
                    )
                ];
            }
        );

        $__Field = new GraphQLObjectType(
            "__Field",
            "Object and Interface types are described by a list of Fields, each of which has a name, potentially a list of arguments, and a return type.",
            function () use (&$__InputValue, &$__Type) {
                return [
                    new GraphQLTypeField(
                        "name",
                        new GraphQLNonNull(new GraphQLString()),
                        "",
                        function (GraphQLTypeField $field) {
                            return $field->getName();
                        }
                    ),
                    new GraphQLTypeField(
                        "description",
                        new GraphQLString(),
                        "",
                        function (GraphQLTypeField $field) {
                            return $field->getDescription();
                        }
                    ),
                    new GraphQLTypeField(
                        "args",
                        new GraphQLNonNull(new GraphQLList(new GraphQLNonNull($__InputValue))),
                        "",
                        function (GraphQLTypeField $field, $args) {
                            return $args["includeDeprecated"]
                                ? $field->getArguments()
                                : array_filter($field->getArguments(), function (GraphQLFieldArgument $arg) {
                                    return $arg->getDeprecationReason() === null;
                                });
                        },
                        [
                            new GraphQLFieldArgument("includeDeprecated", new GraphQLBoolean(), "", false)
                        ]
                    ),
                    new GraphQLTypeField(
                        "type",
                        new GraphQLNonNull($__Type),
                        "",
                        function (GraphQLTypeField $field) {
                            return $field->getType();
                        }
                    ),
                    new GraphQLTypeField(
                        "isDeprecated",
                        new GraphQLNonNull(new GraphQLBoolean()),
                        "",
                        function (GraphQLTypeField $field) {
                            return $field->getDeprecationReason() !== null;
                        }
                    ),
                    new GraphQLTypeField(
                        "deprecationReason",
                        new GraphQLString(),
                        "",
                        function (GraphQLTypeField $field) {
                            return $field->getDeprecationReason();
                        }
                    ),
                ];
            }
        );

        $__InputValue = new GraphQLObjectType(
            "__InputValue",
            "Arguments provided to Fields or Directives and the input fields of an InputObject are represented as Input Values which describe their type and optionally a default value.",
            function () use (&$__Type) {
                return [
                    new GraphQLTypeField(
                        "name",
                        new GraphQLNonNull(new GraphQLString()),
                        "",
                        //$inputValue can either be GraphQLArgument or GraphQLTypeField
                        function ($inputValue) {
                            return $inputValue->getName();
                        }
                    ),
                    new GraphQLTypeField(
                        "description",
                        new GraphQLString(),
                        "",
                        //$inputValue can either be GraphQLArgument or GraphQLTypeField
                        function ($inputValue) {
                            return $inputValue->getDescription();
                        }
                    ),
                    new GraphQLTypeField(
                        "type",
                        new GraphQLNonNull($__Type),
                        "",
                        //$inputValue can either be GraphQLArgument or GraphQLTypeField
                        function ($inputValue) {
                            return $inputValue->getType();
                        }
                    ),
                    new GraphQLTypeField(
                        "defaultValue",
                        new GraphQLString(),
                        "",
                        //$inputValue can either be GraphQLArgument or GraphQLTypeField
                        function ($inputValue) {
                            return strval($inputValue->getDefaultValue());
                        }
                    ),
                    new GraphQLTypeField(
                        "isDeprecated",
                        new GraphQLNonNull(new GraphQLBoolean()),
                        "",
                        //$inputValue can either be GraphQLArgument or GraphQLTypeField
                        function ($inputValue) {
                            return $inputValue->getDeprecationReason() !== null;
                        }
                    ),
                    new GraphQLTypeField(
                        "deprecationReason",
                        new GraphQLString(),
                        "",
                        //$inputValue can either be GraphQLArgument or GraphQLTypeField
                        function ($inputValue) {
                            return $inputValue->getDeprecationReason();
                        }
                    )
                ];
            }
        );

        $__EnumValue = new GraphQLObjectType(
            "__EnumValue",
            "One possible value for a given Enum. Enum values are unique values, not a placeholder for a string or numeric value. However an Enum value is returned in a JSON response as a string.",
            function () {
                return [
                    new GraphQLTypeField(
                        "name",
                        new GraphQLNonNull(new GraphQLString()),
                        "",
                        function (GraphQLEnumValue $enumValue) {
                            return $enumValue->getName();
                        }
                    ),
                    new GraphQLTypeField(
                        "description",
                        new GraphQLString(),
                        "",
                        function (GraphQLEnumValue $enumValue) {
                            return $enumValue->getDescription();
                        }
                    ),
                    new GraphQLTypeField(
                        "isDeprecated",
                        new GraphQLNonNull(new GraphQLBoolean()),
                        "",
                        function (GraphQLEnumValue $enumValue) {
                            return $enumValue->getDeprecationReason() !== null;
                        }
                    ),
                    new GraphQLTypeField(
                        "deprecationReason",
                        new GraphQLString(),
                        "",
                        function (GraphQLEnumValue $enumValue) {
                            return $enumValue->getDeprecationReason();
                        }
                    )
                ];
            }
        );

        $__TypeKind = new GraphQLEnum(
            "__TypeKind",
            "An enum describing what kind of type a given `__Type` is.",
            [
                new GraphQLEnumValue("SCALAR", "Indicates this type is a scalar."),
                new GraphQLEnumValue("OBJECT", "Indicates this type is an object. `fields` and `interfaces` are valid fields."),
                new GraphQLEnumValue("INTERFACE", "Indicates this type is an interface. `fields`, `interfaces`, and `possibleTypes` are valid fields."),
                new GraphQLEnumValue("UNION", "Indicates this type is a union. `possibleTypes` is a valid field."),
                new GraphQLEnumValue("ENUM", "Indicates this type is an enum. `enumValues` is a valid field."),
                new GraphQLEnumValue("INPUT_OBJECT", "Indicates this type is an input object. `inputFields` is a valid field."),
                new GraphQLEnumValue("LIST", "Indicates this type is a list. `ofType` is a valid field."),
                new GraphQLEnumValue("NON_NULL", "Indicates this type is a non-null. `ofType` is a valid field."),
            ]
        );

        $SchemaMetaFieldDef = new GraphQLTypeField(
            "__schema",
            new GraphQLNonNull($__Schema),
            "Access the current type schema of this server.",
            function ($_, $__, $___, $info) {
                return $info["schema"];
            }
        );

        $TypeMetaFieldDef = new GraphQLTypeField(
            "__type",
            $__Type,
            "Request the type information of a single type.",
            function ($_, $args, $__, $info) {
                return $info["schema"]->getType($args["name"]);
            },
            [
                new GraphQLFieldArgument("name", new GraphQLNonNull(new GraphQLString()))
            ]
        );

        $TypeNameMetaFieldDef = new GraphQLTypeField(
            "__typename",
            new GraphQLNonNull(new GraphQLString()),
            "The name of the current Object type at runtime.",
            function ($_, $__, $___, $info) {
                return $info["parentType"]->getName();
            }
        );

        $introspectionTypes = [
            &$__Schema,
            &$__Directive,
            &$__DirectiveLocation,
            &$__Type,
            &$__Field,
            &$__InputValue,
            &$__EnumValue,
            &$__TypeKind
        ];

        return [
            "__Schema" => $__Schema,
            "SchemaMetaFieldDef" => $SchemaMetaFieldDef,
            "TypeMetaFieldDef" => $TypeMetaFieldDef,
            "TypeNameMetaFieldDef" => $TypeNameMetaFieldDef,
        ];
    }

    /**
     * @return GraphQLTypeField
     * @throws BadImplementationError
     */
    static public function getSchemaMetaFieldDef(): GraphQLTypeField
    {
        return self::buildIntrospectionSchemaParts()["SchemaMetaFieldDef"];
    }

    /**
     * @return GraphQLTypeField
     * @throws BadImplementationError
     */
    static public function getTypeMetaFieldDef(): GraphQLTypeField
    {
        return self::buildIntrospectionSchemaParts()["TypeMetaFieldDef"];
    }

    /**
     * @return GraphQLTypeField
     * @throws BadImplementationError
     */
    static public function getTypeNameMetaFieldDef(): GraphQLTypeField
    {
        return self::buildIntrospectionSchemaParts()["TypeNameMetaFieldDef"];
    }
}


/*function isIntrospectionType(string $name){
    global $introspectionTypes;
    return array_reduce($introspectionTypes, function($carry, GraphQLObjectType $type) use($name){
        return $carry || $type->getName()===$name;
    }, false);
}*/
