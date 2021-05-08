<?php

use GraphQL\Types\GraphQLObjectType;
use GraphQL\Types\GraphQLInt;
use GraphQL\Types\GraphQLString;
use GraphQL\Types\GraphQLNonNull;
use GraphQL\Types\GraphQLList;
use GraphQL\Types\GraphQLUnion;
use GraphQL\Fields\GraphQLTypeField;
use GraphQL\Arguments\GraphQLFieldArgument;

require __DIR__ . "/__data.php";

/**
 * Create the Book-Type
 */
$bookType = new GraphQLObjectType("Book", "The Book Type", function () use (&$authorType, &$bookCategoryType, &$authors, &$bookType, &$books) {
    return [
        new GraphQLTypeField(
            "id",
            new GraphQLNonNull(new GraphQLInt()),
            "ID of the Book"
        ),
        new GraphQLTypeField(
            "name",
            new GraphQLString(),
            "Name of the Book",
            function ($parent, $args, &$context) {
                return ($args["prefix"] ?? "") . $parent["name"] . ($context["addOn"] ?? "");
            },
            [
                new GraphQLFieldArgument("prefix", new GraphQLString())
            ]
        ),
        new GraphQLTypeField(
            "author",
            new GraphQLNonNull($authorType),
            "Author of the book",
            function ($parent, $args) use ($authors) {
                $index = array_search($parent["authorId"], array_column($authors, 'id'));
                return $authors[$index];
            }
        )
    ];
});

/**
 * Create the Auhtor-Type
 */
$authorType = new GraphQLObjectType("Author", "The Author Type", function () use (&$bookType, &$books) {
    return [
        new GraphQLTypeField(
            "id",
            new GraphQLNonNull(new GraphQLInt()),
            "ID of the Book"
        ),
        new GraphQLTypeField(
            "name",
            new GraphQLNonNull(new GraphQLString()),
            "Name of the Book"
        ),
        new GraphQLTypeField(
            "books",
            new GraphQLList($bookType),
            "Books of the author",
            function ($parent, $args) use ($books) {
                return array_filter($books, function ($book) use ($parent) {
                    return $book["authorId"] === $parent["id"];
                });
            }
        )
    ];
});

/**
 * Create the SearchResult-Type
 */
$searchResultType = new GraphQLUnion("SearchResult", "Type of data being returned by a search query", [&$bookType, &$authorType]);

/**
 * Create the Query-Type
 */
$queryType = new GraphQLObjectType("Query", "Root Query", function () use (&$books, &$authors, &$bookType, &$authorType, &$withNameInterface, &$bookInputType, &$searchResultType) {
    return [
        new GraphQLTypeField(
            "book",
            $bookType,
            "A single book, requested by the id",
            function ($parent, $args) use ($books) {
                $index = array_search($args["id"], array_column($books, 'id'));
                return $index !== false ? $books[$index] : null;
            },
            [
                new GraphQLFieldArgument("id", new GraphQLNonNull(new GraphQLInt())),
            ]
        ),
        new GraphQLTypeField(
            "author",
            $authorType,
            "A single author",
            function ($parent, $args) use ($authors) {
                $index = array_search($args["id"], array_column($authors, 'id'));
                return $index !== false ? $authors[$index] : null;
            },
            [
                new GraphQLFieldArgument("id", new GraphQLNonNull(new GraphQLInt())),
            ]
        ),
        new GraphQLTypeField(
            "books",
            new GraphQLList($bookType),
            "List of all books",
            function ($_, $args) use ($books) {
                return $books;
            }
        ),
        new GraphQLTypeField(
            "authors",
            new GraphQLList($authorType),
            "List of all authors",
            function () use ($authors) {
                return $authors;
            }
        ),
        new GraphQLTypeField(
            "search",
            new GraphQLList($searchResultType),
            "Search for books and authors",
            function ($_, $args) use ($books, $authors) {
                $allResults = [];
                $s = $args["query"];

                // search for books
                $potentialItems = $s === "" ? $books : array_filter($books, function ($i) use ($s) {
                    return strpos($i["name"], $s) !== false;
                });
                foreach ($potentialItems as $potentialItem) {
                    $allResults[] = $potentialItem;
                }
                // search for authors
                $potentialItems = $s === "" ? $books : array_filter($authors, function ($i) use ($s) {
                    return strpos($i["name"], $s) !== false;
                });
                foreach ($potentialItems as $potentialItem) {
                    $allResults[] = $potentialItem;
                }

                return $allResults;
            },
            [
                new GraphQLFieldArgument("query", new GraphQLNonNull(new GraphQLString()))
            ]
        ),
        new GraphQLTypeField(
            "listOfBooks",
            new GraphQLList($bookType),
            "List of Books based on a given list of ids",
            function ($_, $args) use ($books) {
                $list = [];
                foreach ($args["ids"] as $id) {
                    foreach ($books as $b) {
                        if ($b["id"] == $id) $list[] = $b;
                    }
                }
                return $list;
            },
            [
                new GraphQLFieldArgument("ids", new GraphQLNonNull(new GraphQLList(new GraphQLInt())))
            ]
        )
    ];
});

/**
 * Create the Mutation-Type
 */
$mutationType = new GraphQLObjectType("Mutation", "Root Mutation", function () use (&$bookType) {
    return [
        new GraphQLTypeField(
            "incrementBookIds",
            new GraphQLList($bookType),
            "Increments all book ids by given amount",
            function ($_, $args) use (&$books) {
                foreach ($books as &$book) {
                    $book["id"] += $args["amount"];
                }
                return $books;
            }, [
                new GraphQLFieldArgument("amount", new GraphQLNonNull(new GraphQLInt()), "", 42)
            ]
        )
    ];
});
?>