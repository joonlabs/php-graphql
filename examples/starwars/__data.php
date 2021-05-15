<?php

$humans = [
    "1000" => ["type"=>"human", "id"=>"1000", "name"=>"Luke Skywalker", "friends"=>["1002", "1003", "2000", "2001"], "appearsIn"=>["NEW_HOPE", "EMPIRE", "JEDI"], "homeplanet"=>"Tatooine"],
    "1001" => ["type"=>"human", "id"=>"1001", "name"=>"Darth Vader", "friends"=>["1004"], "appearsIn"=>["NEW_HOPE", "EMPIRE", "JEDI"], "homeplanet"=>"Tatooine"],
    "1002" => ["type"=>"human", "id"=>"1002", "name"=>"Han Solo", "friends"=>["1000", "1003", "2001"], "appearsIn"=>["NEW_HOPE", "EMPIRE", "JEDI"]],
    "1003" => ["type"=>"human", "id"=>"1003", "name"=>"Leia Organa", "friends"=>["1000", "1002", "2000", "2001"], "appearsIn"=>["NEW_HOPE", "EMPIRE", "JEDI"], "homeplanet"=>"Alderaan"],
    "1004" => ["type"=>"human", "id"=>"1004", "name"=>"Wilhuff Tarkin", "friends"=>["1001"], "appearsIn"=>["NEW_HOPE"]],
];

$droids = [
    "2000" => ["type"=>"droid", "id"=>"2000", "name"=>"C-3PO", "friends"=>["1000", "1002", "1003", "2001"], "appearsIn"=>["NEW_HOPE", "EMPIRE", "JEDI"], "homeplanet"=>"Protocol"],
    "2001" => ["type"=>"droid", "id"=>"2000", "name"=>"R2-D2", "friends"=>["1000", "1002", "1003"], "appearsIn"=>["NEW_HOPE", "EMPIRE", "JEDI"], "homeplanet"=>"Astromech"],
];