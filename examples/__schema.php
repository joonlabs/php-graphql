<?php
use GraphQL\Types\GraphQLObjectType;
use GraphQL\Types\GraphQLInt;
use GraphQL\Types\GraphQLString;
use GraphQL\Types\GraphQLNonNull;
use GraphQL\Types\GraphQLList;
use GraphQL\Fields\GraphQLTypeField;
use \GraphQL\Arguments\GraphQLFieldArgument;

require __DIR__."/__data.php";

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
            new GraphQLString(),
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

$authorType = new GraphQLObjectType("Author", "The Author Type", function() use(&$bookType, &$books){
    return[
        new GraphQLTypeField(
            "id",
            new GraphQLNonNull(new GraphQLInt()),
            "ID of the Book"
        ),
        new GraphQLTypeField(
            "name",
            new GraphQLString(),
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

$queryType = new GraphQLObjectType("Query", "Root Query", function() use(&$books, &$authors, &$bookType, &$authorType) {
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
            "authorByBook",
            $authorType,
            "a specific author by book",
            function ($_, $args) use ($books, $authors) {
                $bookIndex = array_search($args["book"]["id"], array_column($books, 'id'));
                $book = $books[$bookIndex];
                $authorIndex = array_search($book["authorId"], array_column($authors, 'id'));
                return $authors[$authorIndex];
            },
            [
                new GraphQLFieldArgument("book", new GraphQLNonNull($bookType))
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
                new GraphQLFieldArgument("ids", new GraphQLList(new GraphQLInt()))
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
                new GraphQLFieldArgument("amount", new GraphQLNonNull(new GraphQLInt()), 200)
            ]
        )
    ];
});
?>