<?php

namespace GraphQL\Servers;

use GraphQL\Execution\Executor;
use GraphQL\Parser\Parser;
use GraphQL\Schemas\Schema;
use GraphQL\Errors\GraphQLError;
use GraphQL\Errors\InternalServerError;
use GraphQL\Utilities\Errors;
use GraphQL\Validation\Rules\ValidationRule;
use GraphQL\Validation\Validator;

class Server
{
    private $schema;
    private $parser;
    private $validator;
    private $executor;
    private $displayInternalServerErrorReason = false;

    /**
     * Server constructor.
     * @param Schema $schema
     */
    public function __construct(Schema $schema)
    {
        // store schema
        $this->schema = $schema;

        // create parser, validator and executor
        $this->parser = new Parser();
        $this->validator = new Validator();
        $this->executor = new Executor();
    }

    /**
     * Enables the printing of the reason for the internal server error.
     * @param bool $enabled
     */
    public function setInternalServerErrorPrint(bool $enabled = true)
    {
        $this->displayInternalServerErrorReason = $enabled;
    }

    public function addValidationRule(ValidationRule $validationRule){
        $this->validator->addAdditionalValidationRule($validationRule);
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
            // no query found -> error
            $this->returnData([
                "errors" => Errors::prettyPrintErrors(
                    [new InternalServerError("No query could be found. Please ensure, that your query is sent via raw POST data, json encoded and accessible via the \"query\" key.")]
                )
            ]);
        } else {
            // try to parse the query
            try {
                // parse the query
                $document = $this->parser->parse($query);

                // validate the query
                $this->validator->validate($this->schema, $document);

                // check if query is valid
                if(!$this->validator->documentIsValid()){
                    // if invalid -> show errors
                    $this->returnData([
                        "errors" => Errors::prettyPrintErrors($this->validator->getErrors())
                    ]);
                }else{
                    // valid -> execute query
                    $result = $this->executor->execute($this->schema, $document, null, null, $variables);
                    $this->returnData($result);
                }

            } catch (GraphQLError $graphQLError) {
                // GraphQLError (while parsing or execution) -> error
                $this->returnData([
                    "errors" => Errors::prettyPrintErrors([$graphQLError])
                ]);
            } catch (\Error $error) {
                // 500 error -> error
                $this->returnData([
                    "errors" => Errors::prettyPrintErrors(
                        [new InternalServerError("An unexpected error occurred during execution" . ($this->displayInternalServerErrorReason ? ": " . $error->getMessage() . ". Trace: " . $error->getTraceAsString() : "."))]
                    )
                ]);
            } catch (\Exception $exception) {
                // Unexpected exception -> error
                $this->returnData([
                    "errors" => Errors::prettyPrintErrors(
                        [new InternalServerError("An unexpected exception occurred during execution." . ($this->displayInternalServerErrorReason ? "\n" . $exception->getMessage() . "\n" . $exception->getTraceAsString() : ""))]
                    )
                ]);
            }
        }
    }

    public function returnData($data){
        echo json_encode($data);
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
     * @return array
     */
    private function getVariables(): array
    {
        // check if variables is sent as raw http body in request as "application/json" or via post fields as "multipart/form-data"
        $headers = apache_request_headers();
        if (array_key_exists("Content-Type", $headers) and $headers["Content-Type"] === "application/json") {
            // raw json string in http body
            $phpInput = json_decode(file_get_contents("php://input"), true);
            return $phpInput["variables"] ?? [];
        }else{
            // query sent via post field
            return json_decode($_POST["variables"] ?? "[]", true);
        }

    }
}

?>