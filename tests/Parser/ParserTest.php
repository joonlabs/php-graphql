<?php

use PHPUnit\Framework\TestCase;
use GraphQL\Parser\Parser;

class ParserTest extends TestCase
{

    /**
     * Allows us to check if comments are ignored
     */
    public function testCheckCommentsAreIgnored()
    {
        $query = '{
          __schema {
            #}
            # __nonExistingField
            queryType {
              name
            }
            #}
          }
        }';

        $parser = new Parser();
        $parser->parse($query);

        self::assertEmpty($parser->getErrors());
    }
    
    /**
     * Allows us to check if the parser can parse null tokens before closing brackets
     */
    public function testCheckParseOfNullTokensBeforeClosingBracket()
    {
        $query = 'query{
          someField(value:null)
        }';

        $parser = new Parser();
        $parser->parse($query);

        self::assertEmpty($parser->getErrors());
    }

    /**
     * Allows us to check if a complex but valid query can be parsed
     */
    public function testComplexIntrospectionQuery()
    {
        $query = '
        query IntrospectionQuery {
            __schema {
              queryType { name }
              mutationType { name }
              subscriptionType { name }
              types {
                ...FullType
              }
              directives {
                name
                description
                args {
                  ...InputValue
                }
                onOperation
                onFragment
                onField
              }
            }
            }
            
            fragment FullType on __Type {
            kind
            name
            description
            fields(includeDeprecated: true) {
              name
              description
              args {
                ...InputValue
              }
              type {
                ...TypeRef
              }
              isDeprecated
              deprecationReason
            }
            inputFields {
              ...InputValue
            }
            interfaces {
              ...TypeRef
            }
            enumValues(includeDeprecated: true) {
              name
              description
              isDeprecated
              deprecationReason
            }
            possibleTypes {
              ...TypeRef
            }
            }
            
            fragment InputValue on __InputValue {
            name
            description
            type { ...TypeRef }
            defaultValue
            }
            
            fragment TypeRef on __Type {
            kind
            name
            ofType {
              kind
              name
              ofType {
                kind
                name
                ofType {
                  kind
                  name
                }
              }
            }
        }';

        $parser = new Parser();
        $parser->parse($query);

        self::assertEmpty($parser->getErrors());
    }


    /**
     * Allows us to check if string quotes in arguments are required
     */
    public function testCheckStringQuotesInArgument()
    {
        $query = '{
          search(query:"Harry Potter){ # missing "
            name
          }
        }';

        $parser = new Parser();
        $parser->parse($query);

        self::assertNotEmpty($parser->getErrors());
    }


    /**
     * Allows us to check if the parser checks for the variable type
     */
    public function testCheckVariableTypeIsPresent()
    {
        $query = 'query QueryWithParams($param:){
          someField
        }';

        $parser = new Parser();
        $parser->parse($query);

        self::assertNotEmpty($parser->getErrors());
    }


    /**
     * Allows us to check if the parser can parse complex and nested variable types
     */
    public function testCheckComplexNestedVariableType()
    {
        $query = 'query QueryWithParams($param:[[Boolean!]!]!, $param2:[[[[[[Int]!]]!]]!]){
          someField
        }';

        $parser = new Parser();
        $parser->parse($query);

        self::assertEmpty($parser->getErrors());
    }


}