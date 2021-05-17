<?php

use PHPUnit\Framework\TestCase;
use GraphQL\Parser\Parser;
use GraphQL\Validation\Validator;

use GraphQL\Tests\StarWarsSchema;

class StarWarsValidationTest extends TestCase
{
    /**
     * Validates a complex but valid query
     */
    public function testValidateComplexButValidQuery()
    {
        $query = '
        query NestedQueryWithFragment {
          hero {
            ...NameAndAppearances
            friends {
              ...NameAndAppearances
              friends {
                ...NameAndAppearances
              }
            }
          }
        }
        fragment NameAndAppearances on Character {
          name
          appearsIn
        }';

        $schema = StarWarsSchema::buildSchema();

        $parser = new Parser();
        $validator = new Validator();

        $parser->parse($query);
        $document = $parser->getParsedDocument();

        $validator->validate($schema, $document);

        self::assertEmpty($validator->getErrors());
    }

    /**
     * Notes that non-existent fields are invalid
     */
    public function testNoteThatNonExistingFieldsAreInvalid()
    {
        $query = '
        query HeroSpaceshipQuery {
          hero {
            favoriteSpaceship
          }
        }';

        $schema = StarWarsSchema::buildSchema();

        $parser = new Parser();
        $validator = new Validator();

        $parser->parse($query);
        $document = $parser->getParsedDocument();

        $validator->validate($schema, $document);

        self::assertNotEmpty($validator->getErrors());
    }

    /**
     * Requires fields on objects
     */
    public function testRequireFieldsOnObjects()
    {
        $query = '
          query HeroNoFieldsQuery {
              hero
            }
        ';

        $schema = StarWarsSchema::buildSchema();

        $parser = new Parser();
        $validator = new Validator();

        $parser->parse($query);
        $document = $parser->getParsedDocument();
        $validator->validate($schema, $document);

        self::assertNotEmpty($validator->getErrors());
    }

    /**
     * Disallows fields on scalars
     */
    public function testDisallowFieldsOnScalars()
    {
        $query = 'query HeroFieldsOnScalarQuery {
          hero {
            name {
              firstCharacterOfName
            }
          }
        }';

        $schema = StarWarsSchema::buildSchema();

        $parser = new Parser();
        $validator = new Validator();

        $parser->parse($query);
        $document = $parser->getParsedDocument();

        $validator->validate($schema, $document);

        self::assertNotEmpty($validator->getErrors());
    }

    /**
     * Disallows object fields on interfaces
     */
    public function testDisallowObjectFieldsOnInterfaces()
    {
        $query = 'query DroidFieldOnCharacter {
          hero {
            name
            primaryFunction
          }
        }';

        $schema = StarWarsSchema::buildSchema();

        $parser = new Parser();
        $validator = new Validator();

        $parser->parse($query);
        $document = $parser->getParsedDocument();

        $validator->validate($schema, $document);

        self::assertNotEmpty($validator->getErrors());
    }

    /**
     * Allows object fields in fragments
     */
    public function testAllowsObjectFieldsInFragments()
    {
        $query = '
        query DroidFieldInFragment {
          hero {
            name
            ...DroidFields
          }
        }
        fragment DroidFields on Droid {
          primaryFunction
        }';

        $schema = StarWarsSchema::buildSchema();

        $parser = new Parser();
        $validator = new Validator();

        $parser->parse($query);
        $document = $parser->getParsedDocument();

        $validator->validate($schema, $document);

        self::assertEmpty($validator->getErrors());
    }

    /**
     * Allows object fields in inline fragments
     */
    public function testAllowsObjectFieldsInInlineFragments()
    {
        $query = '
        query DroidFieldInFragment {
          hero {
            name
            ... on Droid {
              primaryFunction
            }
          }
        }';

        $schema = StarWarsSchema::buildSchema();

        $parser = new Parser();
        $validator = new Validator();

        $parser->parse($query);
        $document = $parser->getParsedDocument();

        $validator->validate($schema, $document);

        self::assertEmpty($validator->getErrors());
    }
}