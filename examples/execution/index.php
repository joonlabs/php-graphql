<?php
// set header to json-format
header("Content-Type: application/json");

// include the php-graphql-autoloader
require '../../src/autoloader.php';
use GraphQL\Parser\Parser;
use GraphQL\Schemas\Schema;
use GraphQL\Execution\Executor;

// pre-fill queryType and mutationType
$queryType = null;
$mutationType = null;

// load types and create schema from them
include __DIR__."/../__schema.php";
$schema = new Schema($queryType, $mutationType);

// build AST
$query = '
    {
        listOfBooks(ids:[1,2,3,8]){
            name
        }
    }
';
$parser = new Parser();
$document = $parser->parse($query);

var_dump($document);
exit();

$executor = new Executor();
$result = $executor->execute($schema, $document, null, null, ["query" => "J. K. Rowling"]);

echo json_encode($result);
//var_dump($result);
?>