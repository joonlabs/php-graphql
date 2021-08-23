<?php

namespace GraphQL\Servers;

use Error;
use Exception;
use GraphQL\Execution\Executor;
use GraphQL\Parser\Parser;
use GraphQL\Schemas\Schema;
use GraphQL\Errors\InternalServerError;
use GraphQL\Utilities\Errors;
use GraphQL\Validation\Rules\ValidationRule;
use GraphQL\Validation\Validator;

/**
 * Class Server
 * @package GraphQL\Servers
 */
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

    /**
     * Adds a custom validation rule (e.g.) for disabling schema introspection
     *
     * @param ValidationRule $validationRule
     */
    public function addValidationRule(ValidationRule $validationRule)
    {
        $this->validator->addAdditionalValidationRule($validationRule);
    }

    /**
     * Handles a request and returns the result. Internally (if not provided via arguments)
     * it looks for a query and variables, and tries to parse and resolve it against the schema.
     *
     * @param string|null $query
     * @param array|null $variables
     * @param string|null $operationName
     * @return array
     */
    public function handle(string $query = null, array $variables = null, string $operationName = null): array
    {
        // obtain query and variables
        $variables = $variables ?? $this->getVariables();
        $operationName = $operationName ?? $this->getOperationName();
        $query = $query ?? $this->getQuery();

        if ($query === null) {
            // no query found -> error
            return [
                "errors" => Errors::prettyPrintErrors(
                    [new InternalServerError("No query could be found. Please ensure, that your query is sent via raw POST data, json encoded and accessible via the \"query\" key.")]
                )
            ];
        } else {
            // try to parse the query
            try {
                // parse query
                $this->parser->parse($query);

                // check if is valid
                if (!$this->parser->queryIsValid()) {
                    // if invalid -> show errors
                    return [
                        "errors" => Errors::prettyPrintErrors($this->parser->getErrors())
                    ];
                }

                // validate query
                $this->validator->validate($this->schema, $this->parser->getParsedDocument());

                // check if is valid
                if (!$this->validator->documentIsValid()) {
                    // if invalid -> show errors
                    return [
                        "errors" => Errors::prettyPrintErrors($this->validator->getErrors())
                    ];
                }

                // execute query
                return $this->executor->execute($this->schema, $this->parser->getParsedDocument(), null, null, $variables, $operationName);

            } catch (Error $error) {
                // 500 error -> error
                return [
                    "errors" => Errors::prettyPrintErrors(
                        [new InternalServerError("An unexpected error occurred during execution" . ($this->displayInternalServerErrorReason ? ": " . $error->getMessage() . ". Trace: " . $error->getTraceAsString() : "."))]
                    )
                ];
            } catch (Exception $exception) {
                // Unexpected exception -> error
                return [
                    "errors" => Errors::prettyPrintErrors(
                        [new InternalServerError("An unexpected exception occurred during execution." . ($this->displayInternalServerErrorReason ? "\n" . $exception->getMessage() . "\n" . $exception->getTraceAsString() : ""))]
                    )
                ];
            }
        }
    }

    /**
     * Handles a request and prints the result.
     */
    public function listen()
    {
        $this->returnData($this->handle());
    }

    /**
     * @param $data
     */
    private function returnData($data)
    {
        echo json_encode($data);
    }

    /**
     * Returns the query string from the raw post data.
     *
     * @return string|null
     */
    private function getQuery(): ?string
    {
        // check if query is sent as raw http body in request as "application/json" or via post fields as "multipart/form-data"
        $headers = apache_request_headers();
        if (array_key_exists("Content-Type", $headers) and str_contains($headers["Content-Type"], "application/json")) {
            // raw json string in http body
            $phpInput = json_decode(file_get_contents("php://input"), true);
            return $phpInput["query"] ?? null;
        } else {
            // query sent via post field
            return $_POST["query"] ?? null;
        }
    }

    /**
     * Returns the operation name string from the raw post data.
     *
     * @return string|null
     */
    private function getOperationName(): ?string
    {
        // check if query is sent as raw http body in request as "application/json" or via post fields as "multipart/form-data"
        $headers = function_exists("getallheaders") ? getallheaders() : [];
        if (array_key_exists("Content-Type", $headers) and str_contains($headers["Content-Type"], "application/json")) {
            // raw json string in http body
            $phpInput = json_decode(file_get_contents("php://input"), true);
            return $phpInput["operationName"] ?? null;
        } else {
            // query sent via post field
            return $_POST["operationName"] ?? null;
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
        $headers = function_exists("getallheaders") ? getallheaders() : [];
        if (array_key_exists("Content-Type", $headers) and str_contains($headers["Content-Type"], "application/json")) {
            // raw json string in http body
            $phpInput = json_decode(file_get_contents("php://input"), true);
            return $phpInput["variables"] ?? [];
        } else {
            // query sent via post field
            return json_decode($_POST["variables"] ?? "[]", true);
        }

    }
}

