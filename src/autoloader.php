<?php
/**
 * Automatically loads all required und used classes.
 */
spl_autoload_register(function ($className) {
    // replace remove GraphQL namespace
    $className = str_replace("GraphQL\\", "", $className);

    // change \ to /
    $className = str_replace("\\", "/", $className);

    // include required file
    include __DIR__ . "/$className.php";
});
?>