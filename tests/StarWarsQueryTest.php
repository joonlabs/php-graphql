<?php

use PHPUnit\Framework\TestCase;
use GraphQL\Parser\Parser;
use GraphQL\Execution\Executor;

use GraphQL\Tests\StarWarsSchema;

class StarWarsQueryTest extends TestCase
{

    private function buildResult(array $result): array
    {
        return [
            "data" => $result
        ];
    }

    /**
     * Correctly identifies R2-D2 as the hero of the Star Wars Saga
     */
    public function testCorrectlyIdentifiesR2D2AsHero()
    {
        $query = '
        query HeroNameQuery {
          hero {
            name
          }
        }
        ';

        $schema = StarWarsSchema::buildSchema();

        $parser = new Parser();
        $executor = new Executor();

        $parser->parse($query);
        $document = $parser->getParsedDocument();

        $result = $executor->execute($schema, $document);

        $properResult = self::buildResult([
            "hero" => [
                "name" => "R2-D2"
            ]
        ]);

        self::assertEquals($properResult, $result);
    }

    /**
     * Allows us to query for the ID and friends of R2-D2
     */
    public function testQueryIDAndFriendsOfR2D2()
    {
        $query = '
        query HeroNameAndFriendsQuery {
          hero {
            id
            name
            friends {
              name
            }
          }
        }
        ';

        $schema = StarWarsSchema::buildSchema();

        $parser = new Parser();
        $executor = new Executor();

        $parser->parse($query);
        $document = $parser->getParsedDocument();

        $result = $executor->execute($schema, $document);

        $properResult = self::buildResult([
            "hero" => [
                "id" => "2001",
                "name" => "R2-D2",
                "friends" => [
                    ["name" => "Luke Skywalker"],
                    ["name" => "Han Solo"],
                    ["name" => "Leia Organa"]
                ]
            ]
        ]);

        self::assertEquals($properResult, $result);
    }

    /**
     * Allows us to query for the friends of friends of R2-D2
     */
    public function testQueryIDAndFriendsOfFriendsOfR2D2()
    {
        $query = '
        query NestedQuery {
          hero {
            name
            friends {
              name
              appearsIn
              friends {
                name
              }
            }
          }
        }
        ';

        $schema = StarWarsSchema::buildSchema();

        $parser = new Parser();
        $executor = new Executor();

        $parser->parse($query);
        $document = $parser->getParsedDocument();

        $result = $executor->execute($schema, $document);

        $properResult = self::buildResult([
            "hero" => [
                "name" => "R2-D2",
                "friends" => [
                    [
                        "name" => "Luke Skywalker",
                        "appearsIn" => ["NEW_HOPE", "EMPIRE", "JEDI"],
                        "friends" => [
                            ["name" => "Han Solo"],
                            ["name" => "Leia Organa"],
                            ["name" => "C-3PO"],
                            ["name" => "R2-D2"],
                        ]
                    ],
                    [
                        "name" => "Han Solo",
                        "appearsIn" => ["NEW_HOPE", "EMPIRE", "JEDI"],
                        "friends" => [
                            ["name" => "Luke Skywalker"],
                            ["name" => "Leia Organa"],
                            ["name" => "R2-D2"],
                        ]
                    ],
                    [
                        "name" => "Leia Organa",
                        "appearsIn" => ["NEW_HOPE", "EMPIRE", "JEDI"],
                        "friends" => [
                            ["name" => "Luke Skywalker"],
                            ["name" => "Han Solo"],
                            ["name" => "C-3PO"],
                            ["name" => "R2-D2"],
                        ]
                    ]
                ]
            ]
        ]);

        self::assertEquals($properResult, $result);
    }

    /**
     * Allows us to query characters directly, using their IDs
     */
    public function testQueryCharactersByTheirIDs()
    {
        $query = '
        query FetchLukeAndC3POQuery {
          human(id: "1000") {
            name
          }
          droid(id: "2000") {
            name
          }
        }
        ';

        $schema = StarWarsSchema::buildSchema();

        $parser = new Parser();
        $executor = new Executor();

        $parser->parse($query);
        $document = $parser->getParsedDocument();

        $result = $executor->execute($schema, $document);

        $properResult = self::buildResult([
            "human" => [
                "name" => "Luke Skywalker",
            ],
            "droid" => [
                "name" => "C-3PO",
            ]
        ]);

        self::assertEquals($properResult, $result);
    }

    /**
     * Allows us to create a generic query, then use it to fetch Luke Skywalker using his ID
     */
    public function testQueryFetchLukeSkywalkerWithGenericQuery()
    {
        $query = '
        query FetchSomeIDQuery($someId: String!) {
          human(id: $someId) {
            name
          }
        }
        ';

        $variableValues = [
            "someId" => "1000"
        ];

        $schema = StarWarsSchema::buildSchema();

        $parser = new Parser();
        $executor = new Executor();

        $parser->parse($query);
        $document = $parser->getParsedDocument();

        $result = $executor->execute($schema, $document, null, null, $variableValues);

        $properResult = self::buildResult([
            "human" => [
                "name" => "Luke Skywalker",
            ]
        ]);

        self::assertEquals($properResult, $result);
    }

    /**
     * Allows us to create a generic query, then use it to fetch Han Solo using his ID
     */
    public function testQueryFetchHanSoloWithGenericQuery()
    {
        $query = '
        query FetchSomeIDQuery($someId: String!) {
          human(id: $someId) {
            name
          }
        }
        ';

        $variableValues = [
            "someId" => "1002"
        ];

        $schema = StarWarsSchema::buildSchema();

        $parser = new Parser();
        $executor = new Executor();

        $parser->parse($query);
        $document = $parser->getParsedDocument();

        $result = $executor->execute($schema, $document, null, null, $variableValues);

        $properResult = self::buildResult([
            "human" => [
                "name" => "Han Solo",
            ]
        ]);

        self::assertEquals($properResult, $result);
    }

    /**
     * Allows us to create a generic query, then pass an invalid ID to get null back
     */
    public function testQueryNonExistingIDWithGenericQuery()
    {
        $query = '
        query humanQuery($id: String!) {
          human(id: $id) {
            name
          }
        }
        ';

        $variableValues = [
            "id" => "not a valid id"
        ];

        $schema = StarWarsSchema::buildSchema();

        $parser = new Parser();
        $executor = new Executor();

        $parser->parse($query);
        $document = $parser->getParsedDocument();

        $result = $executor->execute($schema, $document, null, null, $variableValues);

        $properResult = self::buildResult([
            "human" => null
        ]);

        self::assertEquals($properResult, $result);
    }

    /**
     * Allows us to query for Luke, changing his key with an alias
     */
    public function testQueryForLukeWithAlias()
    {
        $query = '
        query FetchLukeAliased {
          luke: human(id: "1000") {
            name
          }
        }
        ';

        $schema = StarWarsSchema::buildSchema();

        $parser = new Parser();
        $executor = new Executor();

        $parser->parse($query);
        $document = $parser->getParsedDocument();

        $result = $executor->execute($schema, $document);

        $properResult = self::buildResult([
            "luke" => [
                "name" => "Luke Skywalker"
            ]
        ]);

        self::assertEquals($properResult, $result);
    }

    /**
     * Allows us to query for both Luke and Leia, using two root fields and an alias
     */
    public function testQueryForLukeAndLeiaWithAlias()
    {
        $query = '
        query FetchLukeAndLeiaAliased {
          luke: human(id: "1000") {
            name
          }
          leia: human(id: "1003") {
            name
          }
        }
        ';

        $schema = StarWarsSchema::buildSchema();

        $parser = new Parser();
        $executor = new Executor();

        $parser->parse($query);
        $document = $parser->getParsedDocument();

        $result = $executor->execute($schema, $document);

        $properResult = self::buildResult([
            "luke" => [
                "name" => "Luke Skywalker"
            ],
            "leia" => [
                "name" => "Leia Organa"
            ]
        ]);

        self::assertEquals($properResult, $result);
    }

    /**
     * Allows us to query using duplicated content
     */
    public function testQueryUsingDuplicateContent()
    {
        $query = '
        query DuplicateFields {
          luke: human(id: "1000") {
            name
            homePlanet
          }
          leia: human(id: "1003") {
            name
            homePlanet
          }
        }
        ';

        $schema = StarWarsSchema::buildSchema();

        $parser = new Parser();
        $executor = new Executor();

        $parser->parse($query);
        $document = $parser->getParsedDocument();

        $result = $executor->execute($schema, $document);

        $properResult = self::buildResult([
            "luke" => [
                "name" => "Luke Skywalker",
                "homePlanet" => "Tatooine"
            ],
            "leia" => [
                "name" => "Leia Organa",
                "homePlanet" => "Alderaan"
            ]
        ]);

        self::assertEquals($properResult, $result);
    }

    /**
     * Allows us to use a fragment to avoid duplicating content
     */
    public function testQueryUsingFragmentToAvoidDuplicateContents()
    {
        $query = '
        query UseFragment {
          luke: human(id: "1000") {
            ...HumanFragment
          }
          leia: human(id: "1003") {
            ...HumanFragment
          }
        }
        fragment HumanFragment on Human {
          name
          homePlanet
        }
        ';

        $schema = StarWarsSchema::buildSchema();

        $parser = new Parser();
        $executor = new Executor();

        $parser->parse($query);
        $document = $parser->getParsedDocument();

        $result = $executor->execute($schema, $document);

        $properResult = self::buildResult([
            "luke" => [
                "name" => "Luke Skywalker",
                "homePlanet" => "Tatooine"
            ],
            "leia" => [
                "name" => "Leia Organa",
                "homePlanet" => "Alderaan"
            ]
        ]);

        self::assertEquals($properResult, $result);
    }

    /**
     * Allows us to verify that R2-D2 is a droid
     */
    public function testQueryTypenameOfR2D2()
    {
        $query = '
        query CheckTypeOfR2 {
          hero {
            __typename
            name
          }
        }
        ';

        $schema = StarWarsSchema::buildSchema();

        $parser = new Parser();
        $executor = new Executor();

        $parser->parse($query);
        $document = $parser->getParsedDocument();

        $result = $executor->execute($schema, $document);

        $properResult = self::buildResult([
            "hero" => [
                "__typename" => "Droid",
                "name" => "R2-D2"
            ]
        ]);

        self::assertEquals($properResult, $result);
    }

    /**
     * Allows us to verify that Luke is a human
     */
    public function testQueryTypenameOfLukeSkywalker()
    {
        $query = '
        query CheckTypeOfLuke {
          hero(episode: EMPIRE) {
            __typename
            name
          }
        }
        ';

        $schema = StarWarsSchema::buildSchema();

        $parser = new Parser();
        $executor = new Executor();

        $parser->parse($query);
        $document = $parser->getParsedDocument();

        $result = $executor->execute($schema, $document);

        $properResult = self::buildResult([
            "hero" => [
                "__typename" => "Human",
                "name" => "Luke Skywalker"
            ]
        ]);

        self::assertEquals($properResult, $result);
    }

}
