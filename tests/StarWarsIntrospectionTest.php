<?php

use \PHPUnit\Framework\TestCase;
use \GraphQL\Errors\GraphQLError;
use GraphQL\Parser\Parser;
use GraphQL\Execution\Executor;

use GraphQL\Tests\StarWarsSchema;

class StarWarsIntrospectionTest extends TestCase
{

    private function buildResult(array $result): array
    {
        return [
            "data" => $result
        ];
    }

    /**
     * Allows querying the schema for types
     */
    public function testAllowSchemaQueryingForTypes()
    {
        $query = '{
          __schema {
            types {
              name
            }
          }
        }';

        $schema = StarWarsSchema::buildSchema();

        $parser = new Parser();
        $executor = new Executor();

        $parser->parse($query);
        $document = $parser->getParsedDocument();

        $result = $executor->execute($schema, $document);

        $properResult = self::buildResult([
            "__schema" => [
                "types" => [
                    ["name" => "Human"],
                    ["name" => "Character"],
                    ["name" => "String"],
                    ["name" => "Episode"],
                    ["name" => "Droid"],
                    ["name" => "Query"],
                    ["name" => "Boolean"],
                    ["name" => "__Schema"],
                    ["name" => "__Type"],
                    ["name" => "__TypeKind"],
                    ["name" => "__Field"],
                    ["name" => "__InputValue"],
                    ["name" => "__EnumValue"],
                    ["name" => "__Directive"],
                    ["name" => "__DirectiveLocation"],
                ]
            ]
        ]);

        self::assertEqualsCanonicalizing($properResult, $result);
    }

    /**
     * Allows querying the schema for query type
     */
    public function testAllowSchemaQueryingForQueryType()
    {
        $query = '{
          __schema {
            queryType {
              name
            }
          }
        }';

        $schema = StarWarsSchema::buildSchema();

        $parser = new Parser();
        $executor = new Executor();

        $parser->parse($query);
        $document = $parser->getParsedDocument();

        $result = $executor->execute($schema, $document);

        $properResult = self::buildResult([
            "__schema" => [
                "queryType" => [
                    "name" => "Query"
                ]
            ]
        ]);

        self::assertEqualsCanonicalizing($properResult, $result);
    }

    /**
     * Allows querying the schema for a specific type
     */
    public function testAllowSchemaQueryingForSpecificType()
    {
        $query = '{
          __type(name: "Droid") {
            name
          }
        }';

        $schema = StarWarsSchema::buildSchema();

        $parser = new Parser();
        $executor = new Executor();

        $parser->parse($query);
        $document = $parser->getParsedDocument();

        $result = $executor->execute($schema, $document);

        $properResult = self::buildResult([
            "__type" => [
                "name" => "Droid"
            ]
        ]);

        self::assertEqualsCanonicalizing($properResult, $result);
    }

    /**
     * Allows querying the schema for an object kind
     */
    public function testAllowSchemaQueryingForObjectKind()
    {
        $query = '{
          __type(name: "Droid") {
            name
            kind
          }
        }';

        $schema = StarWarsSchema::buildSchema();

        $parser = new Parser();
        $executor = new Executor();

        $parser->parse($query);
        $document = $parser->getParsedDocument();

        $result = $executor->execute($schema, $document);

        $properResult = self::buildResult([
            "__type" => [
                "name" => "Droid",
                "kind" => "OBJECT"
            ]
        ]);

        self::assertEqualsCanonicalizing($properResult, $result);
    }

    /**
     * Allows querying the schema for an interface kind
     */
    public function testAllowSchemaQueryingForInterfaceKind()
    {
        $query = '{
          __type(name: "Character") {
            name
            kind
          }
        }';

        $schema = StarWarsSchema::buildSchema();

        $parser = new Parser();
        $executor = new Executor();

        $parser->parse($query);
        $document = $parser->getParsedDocument();

        $result = $executor->execute($schema, $document);

        $properResult = self::buildResult([
            "__type" => [
                "name" => "Character",
                "kind" => "INTERFACE"
            ]
        ]);

        self::assertEqualsCanonicalizing($properResult, $result);
    }

    /**
     * Allows querying the schema for object fields
     */
    public function testAllowSchemaQueryingForObjectFields()
    {
        $query = '{
          __type(name: "Droid") {
            name
            fields {
              name
              type {
                name
                kind
              }
            }
          }
        }';

        $schema = StarWarsSchema::buildSchema();

        $parser = new Parser();
        $executor = new Executor();

        $parser->parse($query);
        $document = $parser->getParsedDocument();

        $result = $executor->execute($schema, $document);

        $properResult = self::buildResult([
            "__type" => [
                "name" => "Droid",
                "fields" => [
                    [
                        "name" => "id",
                        "type" => ["name" => "NonNull(String)", "kind" => "NON_NULL"]
                    ],
                    [
                        "name" => "name",
                        "type" => ["name" => "String", "kind" => "SCALAR"]
                    ],
                    [
                        "name" => "friends",
                        "type" => ["name" => "List(Character)", "kind" => "LIST"]
                    ],
                    [
                        "name" => "appearsIn",
                        "type" => ["name" => "List(Episode)", "kind" => "LIST"]
                    ],
                    [
                        "name" => "primaryFunction",
                        "type" => ["name" => "String", "kind" => "SCALAR"]
                    ]
                ]
            ]
        ]);

        self::assertEqualsCanonicalizing($properResult, $result);
    }

    /**
     * Allows querying the schema for nested object fields
     */
    public function testAllowSchemaQueryingForNestedObjectFields()
    {
        $query = '{
          __type(name: "Droid") {
            name
            fields {
              name
              type {
                name
                kind
                ofType {
                  name
                  kind
                }
              }
            }
          }
        }';

        $schema = StarWarsSchema::buildSchema();

        $parser = new Parser();
        $executor = new Executor();

        $parser->parse($query);
        $document = $parser->getParsedDocument();

        $result = $executor->execute($schema, $document);

        $properResult = self::buildResult([
            "__type" => [
                "name" => "Droid",
                "fields" => [
                    [
                        "name" => "id",
                        "type" => [
                            "name" => "NonNull(String)",
                            "kind" => "NON_NULL",
                            "ofType" => [
                                "name" => "String",
                                "kind" => "SCALAR"
                            ]
                        ]
                    ],
                    [
                        "name" => "name",
                        "type" => [
                            "name" => "String",
                            "kind" => "SCALAR",
                            "ofType" => null
                        ]
                    ],
                    [
                        "name" => "friends",
                        "type" => [
                            "name" => "List(Character)",
                            "kind" => "LIST",
                            "ofType" => [
                                "name" => "Character",
                                "kind" => "INTERFACE"
                            ]
                        ]
                    ],
                    [
                        "name" => "appearsIn",
                        "type" => [
                            "name" => "List(Episode)",
                            "kind" => "LIST",
                            "ofType" => [
                                "name" => "Episode",
                                "kind" => "ENUM"
                            ]
                        ]
                    ],
                    [
                        "name" => "primaryFunction",
                        "type" => [
                            "name" => "String",
                            "kind" => "SCALAR",
                            "ofType" => null
                        ]
                    ]
                ]
            ]
        ]);

        self::assertEqualsCanonicalizing($properResult, $result);
    }

    /**
     * Allows querying the schema for documentation
     */
    public function testAllowSchemaQueryingForDocumentation()
    {
        $query = '{
          __type(name: "Droid") {
            name
            description
          }
        }';

        $schema = StarWarsSchema::buildSchema();

        $parser = new Parser();
        $executor = new Executor();

        $parser->parse($query);
        $document = $parser->getParsedDocument();

        $result = $executor->execute($schema, $document);

        $properResult = self::buildResult([
            "__type" => [
                "name" => "Droid",
                "description" => "A mechanical creature in the Star Wars universe."
            ]
        ]);

        self::assertEqualsCanonicalizing($properResult, $result);
    }
}