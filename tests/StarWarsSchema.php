<?php

namespace GraphQL\Tests;

use GraphQL\Types\GraphQLObjectType;
use GraphQL\Types\GraphQLEnum;
use GraphQL\Types\GraphQLEnumValue;
use GraphQL\Types\GraphQLString;
use GraphQL\Types\GraphQLInterface;
use GraphQL\Types\GraphQLNonNull;
use GraphQL\Types\GraphQLList;
use GraphQL\Fields\GraphQLTypeField;
use GraphQL\Arguments\GraphQLFieldArgument;
use GraphQL\Schemas\Schema;

class StarWarsSchema{
    private static $humans = [
        "1000" => ["type" => "human", "id" => "1000", "name" => "Luke Skywalker", "friends" => ["1002", "1003", "2000", "2001"], "appearsIn" => ["NEW_HOPE", "EMPIRE", "JEDI"], "homePlanet" => "Tatooine"],
        "1001" => ["type" => "human", "id" => "1001", "name" => "Darth Vader", "friends" => ["1004"], "appearsIn" => ["NEW_HOPE", "EMPIRE", "JEDI"], "homePlanet" => "Tatooine"],
        "1002" => ["type" => "human", "id" => "1002", "name" => "Han Solo", "friends" => ["1000", "1003", "2001"], "appearsIn" => ["NEW_HOPE", "EMPIRE", "JEDI"]],
        "1003" => ["type" => "human", "id" => "1003", "name" => "Leia Organa", "friends" => ["1000", "1002", "2000", "2001"], "appearsIn" => ["NEW_HOPE", "EMPIRE", "JEDI"], "homePlanet" => "Alderaan"],
        "1004" => ["type" => "human", "id" => "1004", "name" => "Wilhuff Tarkin", "friends" => ["1001"], "appearsIn" => ["NEW_HOPE"]],
    ];

    private static $droids = [
        "2000" => ["type" => "droid", "id" => "2000", "name" => "C-3PO", "friends" => ["1000", "1002", "1003", "2001"], "appearsIn" => ["NEW_HOPE", "EMPIRE", "JEDI"], "homePlanet" => "Protocol"],
        "2001" => ["type" => "droid", "id" => "2001", "name" => "R2-D2", "friends" => ["1000", "1002", "1003"], "appearsIn" => ["NEW_HOPE", "EMPIRE", "JEDI"], "homePlanet" => "Astromech"],
    ];

    public static function buildSchema(): Schema
    {
        $humans = self::$humans;
        $droids = self::$droids;

        /**
         * EPISODE
         */
        $Episode = new GraphQLEnum("Episode", "One of the films in the Star Wars Trilogy.", [
            new GraphQLEnumValue("NEW_HOPE", "Released in 1977."),
            new GraphQLEnumValue("EMPIRE", "Released in 1980."),
            new GraphQLEnumValue("JEDI", "Released in 1983.")
        ]);

        /**
         * CHARACTER
         */
        $Character = new GraphQLInterface("Character", "A character in the Star Wars Trilogy.", function () use (&$Character, &$Episode) {
            return [
                new GraphQLTypeField("id", new GraphQLNonNull(new GraphQLString()), "The id of the character."),
                new GraphQLTypeField("name", new GraphQLString(), "The name of the character."),
                new GraphQLTypeField("friends", new GraphQLList($Character), "The friends of the character, or an empty list if they have none."),
                new GraphQLTypeField("appearsIn", new GraphQLList($Episode), "Which movies they appear in."),
            ];
        }, function ($character) {
            if ($character["type"] === "human") {
                return "Human";
            }
            return "Droid";
        });

        /**
         * HUMAN
         */
        $Human = new GraphQLObjectType("Human", "A humanoid creature in the Star Wars universe.", function () use (&$Character, &$Episode, &$humans, &$droids) {
            return [
                new GraphQLTypeField("id", new GraphQLNonNull(new GraphQLString()), "The id of the human."),
                new GraphQLTypeField("name", new GraphQLString(), "The name of the human."),
                new GraphQLTypeField("friends", new GraphQLList($Character), "The friends of the human, or an empty list if they have none.", function ($character) use (&$humans, &$droids) {
                    return array_map(function ($friendId) use (&$humans, &$droids) {
                        return $humans[$friendId] ?? $droids[$friendId];
                    }, $character["friends"]);
                }),
                new GraphQLTypeField("appearsIn", new GraphQLList($Episode), "Which movies they appear in."),
                new GraphQLTypeField("homePlanet", new GraphQLString(), "The home planet of the human, or null if unknown.")
            ];
        }, [
            $Character
        ]);

        /**
         * DROID
         */
        $Droid = new GraphQLObjectType("Droid", "A mechanical creature in the Star Wars universe.", function () use (&$Character, &$Episode, &$humans, &$droids) {
            return [
                new GraphQLTypeField("id", new GraphQLNonNull(new GraphQLString()), "The id of the droid."),
                new GraphQLTypeField("name", new GraphQLString(), "The name of the droid."),
                new GraphQLTypeField("friends", new GraphQLList($Character), "The friends of the droid, or an empty list if they have none.", function ($character) use (&$humans, &$droids) {
                    return array_map(function ($friendId) use (&$humans, &$droids) {
                        return $humans[$friendId] ?? $droids[$friendId];
                    }, $character["friends"]);
                }),
                new GraphQLTypeField("appearsIn", new GraphQLList($Episode), "Which movies they appear in."),
                new GraphQLTypeField("primaryFunction", new GraphQLString(), "The primary function of the droid.")
            ];
        }, [
            $Character
        ]);


        /**
         * QUERY
         */
        $Query = new GraphQLObjectType("Query", "Root Query", function () use (&$Episode, &$Character, &$Human, &$Droid, &$humans, &$droids) {
            return [
                new GraphQLTypeField("hero", $Character, "", function ($_, $args) use (&$humans, &$droids) {
                    if (($args["episode"] ?? null) === "EMPIRE") {
                        return $humans["1000"]; // Luke Skywalker
                    }
                    return $droids["2001"]; // R2-D2
                }, [
                        new GraphQLFieldArgument("episode", $Episode, "If omitted, returns the hero of the whole saga. If provided, returns the hero of that particular episode")
                    ]
                ),
                new GraphQLTypeField("human", $Human, "", function ($_, $args) use (&$humans) {
                    return $humans[$args["id"]] ?? null;
                }, [
                        new GraphQLFieldArgument("id", new GraphQLNonNull(new GraphQLString()), "id of the human")
                    ]
                ),
                new GraphQLTypeField("droid", $Droid, "", function ($_, $args) use (&$droids) {
                    return $droids[$args["id"]] ?? null;
                }, [
                        new GraphQLFieldArgument("id", new GraphQLNonNull(new GraphQLString()), "id of the droid")
                    ]
                )
            ];
        });

        return new Schema($Query);
    }
}
