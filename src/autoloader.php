<?php
/**
 * php-graphql features an own autoloader, for cases where this library is not
 * used and included via composer, but rather as a git submodule. If you are using
 * composer for managing libraries, etc. you can ignore this file and use the default
 * composer autoloader via `require '/vendor/autoload.php'`. However if you prefer using this
 * project via git submodules, you can use this autoloader via `require /php-graphql/src/autoloader.php`.
 */
spl_autoload_register(function ($className) {
    // remove GraphQL namespace
    $className = str_replace("GraphQL\\", "", $className);

    // change \ to /
    $className = str_replace("\\", "/", $className);

    // include required file
    include __DIR__ . "/$className.php";
});
?>