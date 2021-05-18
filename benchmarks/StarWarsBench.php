<?php

use GraphQL\Errors\BadImplementationError;
use GraphQL\Errors\GraphQLError;
use GraphQL\Errors\UnexpectedTokenError;
use GraphQL\Execution\Executor;
use GraphQL\Introspection\Introspection;
use GraphQL\Parser\Parser;
use GraphQL\Tests\StarWarsSchema;
use GraphQL\Validation\Validator;

/**
 * @Warmup(3)
 * @Revs(10)
 * @Iterations(2)
 */
class StarWarsBench{
    /**
     * @param $query
     * @throws BadImplementationError
     * @throws GraphQLError
     * @throws UnexpectedTokenError
     */
    public function validateAndExecuteQuery($query)
    {
        $schema = StarWarsSchema::buildSchema();

        $parser = new Parser();
        $validator = new Validator();
        $executor = new Executor();

        $parser->parse($query);
        $document = $parser->getParsedDocument();

        $validator->validate($schema, $document);

        $executor->execute($schema, $document);
    }

    /**
     * @throws BadImplementationError
     * @throws GraphQLError
     */
    public function benchBuildSchema()
    {
        StarWarsSchema::buildSchema();
    }

    /**
     * @throws BadImplementationError
     * @throws GraphQLError
     * @throws UnexpectedTokenError
     */
    public function benchHeroQuery()
    {
        $query = '
        query HeroNameQuery {
          hero {
            name
          }
        }
        ';

        $this->validateAndExecuteQuery($query);
    }

    /**
     * @throws BadImplementationError
     * @throws GraphQLError
     * @throws UnexpectedTokenError
     */
    public function benchNestedQuery()
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

        $this->validateAndExecuteQuery($query);
    }

    /**
     * @throws BadImplementationError
     * @throws GraphQLError
     * @throws UnexpectedTokenError
     */
    public function benchQueryWithFragment()
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

        $this->validateAndExecuteQuery($query);
    }

    /**
     * @throws BadImplementationError
     * @throws GraphQLError
     * @throws UnexpectedTokenError
     */
    public function benchQueryWithInterfaceFragment()
    {
        $query = '
        query UseInterfaceFragment {
          luke: human(id: "1000") {
            ...CharacterFragment
          }
          leia: human(id: "1003") {
            ...CharacterFragment
          }
        }
        fragment CharacterFragment on Character {
          name
        }
        ';

        $this->validateAndExecuteQuery($query);
    }

    /**
     * @throws BadImplementationError
     * @throws GraphQLError
     * @throws UnexpectedTokenError
     */
    public function benchQueryIntrospection()
    {
        $query = Introspection::getIntrospectionQuery();

        $this->validateAndExecuteQuery($query);
    }


}