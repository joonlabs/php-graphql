<?php

namespace GraphQL\Variables;

use GraphQL\Errors\BadUserInputError;

class GraphQLVariableHolder{
    private $variables;

    /**
     * Creates a GraphQLVariableHolder object to hold GraphQL variables inside.
     *
     * GraphQLVariableHolder constructor.
     * @param array $variables
     */
    public function __construct(array $variables)
    {
        $this->variables = $variables;
    }

    /**
     * Returns a specific variable by it's identifier.
     *
     * @param $id
     * @return mixed
     * @throws BadUserInputError
     */
    public function __get($id){
        if(array_key_exists($id, $this->variables)){
            return $this->variables[$id];
        }else{
            throw new BadUserInputError("The variable \"$id\" does not exists in transmitted variables.");
        }
    }
}