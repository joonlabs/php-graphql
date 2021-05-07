<?php
// set header to json-format
header("Content-Type: application/json");

// include the php-graphql-autoloader
require '../../src/autoloader.php';

use GraphQL\Errors\GraphQLError;
use GraphQL\Parser\Parser;
use GraphQL\Schemas\Schema;
use GraphQL\Execution\Executor;
use GraphQL\Validation\Validator;

// pre-fill queryType and mutationType
$queryType = null;
$mutationType = null;

// load types and create schema from them
include __DIR__."/../__schema.php";
$schema = new Schema($queryType, $mutationType);

// build AST
$query = '
    {
        #listOfBooks(ids:[1,2,3]){
        #    ...B
        #}
        search(query:{name:"Harry"}){
            #name @skip(if:false)
            ... B
        }
    }
        
    fragment A on Author{
        id
    }
    
    fragment B on Book{
        name 
        ... A
    }
';
$parser = new Parser();
$document = $parser->parse($query);
//echo json_encode($document); exit();

$validator = new Validator();
$validator->validate($schema, $document);

$valErrors = (array_map(function(GraphQLError $error){
    return [
        "message" => $error->getMessage(),
        "locations" => $error->getLocations(),
        "path" => $error->getPath(),
        "extensions" => [
            "code" => $error->getErrorCode()
        ]
    ];
}, $validator->getErrors()));

if(count($valErrors)>0){
    echo json_encode($valErrors);
    exit();
}

$executor = new Executor();
$result = $executor->execute($schema, $document, null, null, ["bookList" => [1,2,3,4,6]]);

echo json_encode($result);
?>