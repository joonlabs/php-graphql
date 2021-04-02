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
$query = "
    {
        book(id:2){
            ... GetName
        }
    }
    
    fragment GetName on Book{
        name
        ... on Book{
            id
        }
    }
";
$parser = new Parser();
$document = $parser->parse($query);

$executor = new Executor();
$result = $executor->execute($schema, $document);

echo json_encode($result);
//var_dump($result);
?>