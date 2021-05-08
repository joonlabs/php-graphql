<?php
use GraphQL\Types\GraphQLObjectType;
use GraphQL\Types\GraphQLInt;
use GraphQL\Types\GraphQLString;
use GraphQL\Types\GraphQLNonNull;
use GraphQL\Types\GraphQLList;
use GraphQL\Types\GraphQLUnion;
use GraphQL\Types\GraphQLInputObjectType;
use GraphQL\Types\GraphQLInterface;
use GraphQL\Fields\GraphQLTypeField;
use GraphQL\Arguments\GraphQLFieldArgument;

require __DIR__."/__data.php";

$withNameInterface = new GraphQLInterface("WithName", "All Types with Name", function(){
    return [
        new GraphQLTypeField(
            "name",
            new GraphQLString(),
            "Name of the Entry"
        ),
    ];
});

// build schema
$bookType = new GraphQLObjectType("Book", "The Book Type", function() use(&$authorType, &$bookCategoryType, &$authors, &$bookType, &$books) {
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
            function($parent, $args, &$context){
                return ($args["prefix"]??"").$parent["name"].($context["addOn"] ?? "");
            },
            [
                new GraphQLFieldArgument("prefix", new GraphQLString())
            ]
        ),
        new GraphQLTypeField(
            "authorId",
            new GraphQLInt(),
            "Author Id of the Book"
        ),
        new GraphQLTypeField(
            "author",
            new GraphQLNonNull($authorType),
            "Author of the book",
            function($parent, $args) use($authors){
                $index = array_search($parent["authorId"], array_column($authors, 'id'));
                return $authors[$index];
            }
        ),
        new GraphQLTypeField(
            "nextBook",
            $bookType,
            "The next book in the data source",
            function($parent, $args) use($books){
                $index = array_search($parent["id"]+1, array_column($books, 'id'));
                return $index!==false ? $books[$index] : null;
            }
        )
    ];
});
$bookType->setInterfaces([&$withNameInterface]);

$authorType = new GraphQLObjectType("Author", "The Author Type", function() use(&$bookType, &$books){
    return[
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
            function($parent, $args) use($books){
                return array_filter($books, function($book) use($parent){
                    return $book["authorId"]===$parent["id"];
                });
            }
        )
    ];
});
$authorType->setInterfaces([&$withNameInterface]);

$searchResultType = new GraphQLUnion("SearchResultType", "Type being returned by a search query", [&$bookType, &$authorType]);

$bookInputType = new GraphQLInputObjectType(
    "BookInput",
    "This is the book input type",
    function(){
        return [
            new GraphQLTypeField(
                "id",
                new GraphQLNonNull(new GraphQLInt()),
                "Id of the book"
            ),
            new GraphQLTypeField(
                "name",
                new GraphQLString(),
                "Name of the book"
            )
        ];
    }
);


$queryType = new GraphQLObjectType("Query", "Root Query", function() use(&$books, &$authors, &$bookType, &$authorType, &$withNameInterface, &$bookInputType, &$searchResultType) {
    return [
        new GraphQLTypeField(
            "book",
            $bookType,
            "A single book, requested by the id",
            function ($parent, $args, &$context) use ($books) {
                $context["addOn"] = " : Julius";
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
            "search for book or author",
            function($_, $args) use ($books, $authors){
                $allResults = [];
                $s = $args["query"]["name"] ?? "o";
                // search for books
                $potentialItems = array_filter($books, function($i) use($s){return strpos($i["name"], $s)!==false;});
                foreach($potentialItems as $potentialItem){
                    $allResults[] = $potentialItem;
                }
                // search for authors
                $potentialItems = array_filter($authors, function($i) use($s){return strpos($i["name"], $s)!==false;});
                foreach($potentialItems as $potentialItem){
                    $allResults[] = $potentialItem;
                }

                return $allResults;
            },
            [
                new GraphQLFieldArgument("query", new GraphQLNonNull(
                    new GraphQLInputObjectType(
                        "queryInput",
                        "BLA",
                        function(){
                            return [
                                new GraphQLTypeField("name", new GraphQLNonNull(new GraphQLString()))
                            ];
                        }
                    )
                ))
            ]
        ),
        new GraphQLTypeField(
            "authorByBook",
            $authorType,
            "a specific author by book",
            function ($_, $args) use ($books, $authors) {
                $bookIndex = array_search($args["book"]["id"], array_column($books, 'id'));
                if($bookIndex===false) return null;
                $book = $books[$bookIndex];
                $authorIndex = array_search($book["authorId"], array_column($authors, 'id'));
                return $authors[$authorIndex];
            },
            [
                new GraphQLFieldArgument("book", new GraphQLNonNull($bookInputType))
            ]
        ),
        new GraphQLTypeField(
            "listOfBooks",
            new GraphQLList($bookType),
            "List of all books",
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

$mutationType = new GraphQLObjectType("Mutation", "Root Mutation", function() use(&$bookType) {
    return [
        new GraphQLTypeField(
            "incrementBookIds",
            new GraphQLList($bookType),
            "increments all book ids by given amount",
            function ($_, $args) use (&$books) {
                foreach ($books as &$book) {
                    $book["id"] += $args["amount"];
                }
                return $books;
            }, [
                new GraphQLFieldArgument("amount", new GraphQLNonNull(new GraphQLInt()), "", 200)
            ]
        )
    ];
});
?>