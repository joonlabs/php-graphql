<?php
// set header to json-format
header("Content-Type: application/json");

// include the php-graphql-autoloader
require '../../src/autoloader.php';

use GraphQL\Servers\Server;
use GraphQL\Schemas\Schema;

// pre-fill queryType and mutationType
$queryType = null;
$mutationType = null;

// load types and create schema from them
include __DIR__ . "/__schema.php";
$schema = new Schema($queryType, $mutationType);

// build server
$server = new Server($schema);
$server->setInternalServerErrorPrint(true);
$server->listen();
