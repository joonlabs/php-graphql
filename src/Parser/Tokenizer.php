<?php

namespace GraphQL\Parser;

use GraphQL\Errors\GraphQLError;
use GraphQL\Errors\UnexpectedTokenError;

/**
 * Class Tokenizer
 * @package GraphQL\Parser
 */
class Tokenizer
{
    private $string = "";
    private $cursor = 0;

    private $location = [
        "line" => 1,
        "column" => 1
    ];
    private $locationHistory = [];

    private $spec = [
        // --------------------------------------------
        // New-Line(s):
        ['/^\n+/', "NL"],

        // --------------------------------------------
        // Whitespace(s):
        ['/^\s+/', null],

        // --------------------------------------------
        // Comments
        ['/^#.*/', null],

        // --------------------------------------------
        // Symbols, delimeters
        ['/^,/', ","],
        ['/^:/', ":"],
        ['/^\$/', "$"],
        ['/^\=/', "="],
        ['/^\!/', "!"],
        ['/^\(/', "("],
        ['/^\)/', ")"],
        ['/^\{/', "{"],
        ['/^\}/', "}"],
        ['/^\[/', "["],
        ['/^\]/', "]"],
        ['/^@/', "@"],
        ['/^\.\.\./', "..."],

        // --------------------------------------------
        // Types
        ['/^query(?![_A-Za-z:\)])/', "QUERY"],
        ['/^mutation(?![_A-Za-z:\)])/', "MUTATION"],
        ['/^subscription(?![_A-Za-z:\)])/', "SUBSCRIPTION"],

        // --------------------------------------------
        // Keywords
        ['/^fragment(?![_A-Za-z:\)])/', "FRAGMENT"],
        ['/^on(?![_A-Za-z:\)])/', "ON"],

        // --------------------------------------------
        // String
        ['/^"[^"]*"/', "STRING"],

        // --------------------------------------------
        // Float
        ['/^[-]?\d+\.\d+/', "FLOAT"],

        // --------------------------------------------
        // Integer
        ['/^[-]?\d+/', "INTEGER"],

        // --------------------------------------------
        // Boolean
        ['/^(true|false)(?![_A-Za-z:])/', "BOOLEAN"],

        // --------------------------------------------
        // Null
        ['/^(null)(?![_A-Za-z:\)])/', "NULL"],

        // --------------------------------------------
        // Names
        ['/^[_A-Za-z][_0-9A-Za-z]*/', "NAME"],
        //['/^[\x{0009}\x{000A}\x{000D}\x{0020}-\x{FFFF}]+/u', "SOURCETEXT"],
    ];

    /**
     * @param $string
     */
    public function init($string)
    {
        $this->string = $string;
        $this->cursor = 0;
    }

    /**
     * Returns, whether there are more potential tokens available to tokenize.
     *
     * @return bool
     */
    public function hasMoreTokens(): bool
    {
        return $this->cursor < strlen($this->string);
    }

    /**
     * Returns the next token (type and value) and moves the cursor accordingly
     * @return array|null
     * @throws UnexpectedTokenError
     */
    public function getNextToken(): ?array
    {

        $this->addToLocationHistory();

        if (!$this->hasMoreTokens()) {
            return null;
        }

        $string = substr($this->string, $this->cursor);

        foreach ($this->spec as [$regexp, $tokenType]) {
            $tokenValue = $this->match($regexp, $string);

            // could not match this rule, continue with next one
            if ($tokenValue === null) {
                continue;
            }

            // this token can be skipped (newline)
            if ($tokenType === "NL") {
                $this->location["line"] += strlen($tokenValue);
                $this->location["column"] = 1;
                return $this->getNextToken();
            }

            // this token can be skipped, e.g. whitespace or comment
            if ($tokenType === null) {
                $this->location["column"] += strlen($tokenValue);
                return $this->getNextToken();
            }

            return [
                "type" => $tokenType,
                "value" => $tokenValue,
            ];
        }

        throw new UnexpectedTokenError("Unexpetced token : \"" . $string[0] . "\"", $this->getLastLocation());
    }

    /**
     * Implenents LL(2)-Parsing shortcut, as this is necesarry for Selection to dertermine the fragment type
     *
     * @return array|null
     * @throws UnexpectedTokenError
     */
    public function glimpsAtNextToken(): ?array
    {
        $cursor = $this->cursor;
        $glimpsToken = $this->getNextToken();
        $this->cursor = $cursor;
        return $glimpsToken;
    }

    /**
     * Returns whether a regular expression matches a string and if it does so, it updates the cursor.
     *
     * @param string $regexp
     * @param string $string
     * @return mixed|null
     */
    public function match(string $regexp, string $string)
    {
        $matches = [];
        preg_match($regexp, $string, $matches);

        if (count($matches) == 0) {
            return null;
        }

        $this->cursor += strlen($matches[0]); // add matched length to cursor

        return $matches[0];
    }

    /**
     * Returns the current location in the file based on line and column.
     *
     * @return int[]
     */
    public function getLocation(): array
    {
        return $this->location;
    }

    /**
     * Returns the last location visited in the query
     *
     * @return int[]|mixed
     */
    public function getLastLocation()
    {
        $historyLength = count($this->locationHistory);
        if ($historyLength <= 1)
            return $this->getLocation();
        return $this->locationHistory[$historyLength - 1];
    }

    /**
     * Adds the current location to the history
     */
    private function addToLocationHistory()
    {
        $this->locationHistory[] = $this->getLocation();
    }
}