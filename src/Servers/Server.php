<?php

namespace GraphQL\Servers;

use GraphQL\Schemas\Schema;
use GraphQL\Errors\GraphQLError;
use GraphQL\Fields\GraphQLQuery;
use GraphQL\Fields\GraphQLMutation;
use GraphQL\Resolvers\ErrorResolver;
use GraphQL\Parsers\GraphQLQueryParser;
use GraphQL\Errors\InternalServerError;
use GraphQL\Variables\GraphQLVariableHolder;
use GraphQL\AbstractSyntaxTreeTranslators\AbstractSyntaxTreeTranslator;

class Server
{
    private $schema;
    private $parser;
    private $displayInternalServerErrorReason = false;

    /**
     * Server constructor.
     * @param Schema $schema
     */
    public function __construct(Schema $schema)
    {
        // store schema
        $this->schema = $schema;

        // create parser
        $this->parser = new GraphQLQueryParser();
    }

    /**
     * Enables the printing of the reason for the internal server error.
     * @param bool $enable
     */
    public function enableInternalServerErrorPrint(bool $enable = true)
    {
        $this->displayInternalServerErrorReason = $enable;
    }

    /**
     * Looks for a query and variables, and tries to parse and resolve it against the schema.
     *
     */
    public function listen()
    {
        // obtain query and variables
        $variables = $this->getVariables();
        $query = $this->getQuery();

        if ($query === null) {
            try {
                throw new InternalServerError("No query could be found. Please ensure, that your query is sent via raw POST data, json encoded and accessible via the \"query\" key.");
            } catch (GraphQLError $graphQLError) {
                echo json_encode(
                    ["errors" => [ErrorResolver::resolve($graphQLError)]]
                );
            }
        } else {
            try {
                // create abstract syntax tree
                $abstractSyntaxTree = $this->parser->parse($query);
                // translate abstract syntrax tree into an operation
                $operation = AbstractSyntaxTreeTranslator::translate($abstractSyntaxTree);

                if ($operation instanceof GraphQLQuery) {
                    $result = $this->schema->propagateQuery($operation, $variables);
                    echo json_encode(
                        ["data" => $result]
                    );
                } else if ($operation instanceof GraphQLMutation) {
                    $result = $this->schema->propagateMutation($operation, $variables);
                    echo json_encode(
                        ["data" => $result]
                    );
                }
            } catch (GraphQLError $graphQLError) {
                echo json_encode(
                    ["errors" => [ErrorResolver::resolve($graphQLError)]]
                );
            } catch (\Error $error) {
                echo json_encode(
                    ["errors" => [ErrorResolver::resolve(new InternalServerError(
                        "An unexpected error occurred during execution" .
                        ($this->displayInternalServerErrorReason ? ": " . $error->getMessage() . ". Trace: " . $error->getTraceAsString() : ".")
                    ))]]
                );
            } catch (\Exception $exception) {
                echo json_encode(
                    ["errors" => [ErrorResolver::resolve(new InternalServerError(
                        "An unexpected exception occurred during execution." .
                        ($this->displayInternalServerErrorReason ? "\n" . $exception->getMessage() . "\n" . $exception->getTraceAsString() : "")
                    ))]]
                );
            }
        }
    }

    /**
     * Returns the query string from the raw post data.
     *
     * @return string|null
     */
    private function getQuery()
    {
        // check if query is sent as raw http body in request as "application/json" or via post fields as "multipart/form-data"
        $headers = apache_request_headers();
        if (array_key_exists("Content-Type", $headers) and $headers["Content-Type"] === "application/json") {
            // raw json string in http body
            $phpInput = json_decode(file_get_contents("php://input"), true);
            return $phpInput["query"] ?? null;
        }else{
            // query sent via post field
            return $_POST["query"] ?? null;
        }
    }

    /**
     * Returns the variables, sent by raw post data.
     *
     * @return GraphQLVariableHolder
     */
    private function getVariables(): GraphQLVariableHolder
    {
        // check if variables is sent as raw http body in request as "application/json" or via post fields as "multipart/form-data"
        $headers = apache_request_headers();
        if (array_key_exists("Content-Type", $headers) and $headers["Content-Type"] === "application/json") {
            // raw json string in http body
            $phpInput = json_decode(file_get_contents("php://input"), true);
            return new GraphQLVariableHolder($phpInput["variables"] ?? []);
        }else{
            // query sent via post field
            return new GraphQLVariableHolder(json_decode($_POST["variables"] ?? "[]", true));
        }

    }
}

?>