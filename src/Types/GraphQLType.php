<?php

namespace GraphQL\Types;

use GraphQL\Errors\BadUserInputError;
use GraphQL\Errors\GraphQLError;
use GraphQL\Fields\GraphQLQueryField;
use GraphQL\Fields\GraphQLTypeField;
use GraphQL\Resolvers\QueryResolver;
use GraphQL\Variables\GraphQLVariableHolder;

abstract class GraphQLType
{
    protected $type;
    protected $description;

    public function getName() : string
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    public function getNamedType(): GraphQLType
    {
        if ($this instanceof GraphQLNonNull or $this instanceof GraphQLList) {
            return $this->getInnerType()->getNamedType();
        }
        return $this;
    }

    public function isAbstractType(): bool
    {
        return $this instanceof GraphQLAbstractType;
    }

    public function isBooleanType(): bool
    {
        return $this instanceof GraphQLBoolean;
    }

    public function isEnumType(): bool
    {
        return $this instanceof GraphQLEnum;
    }

    public function isFloatType(): bool
    {
        return $this instanceof GraphQLFloat;
    }

    public function isIDType(): bool
    {
        return $this instanceof GraphQLID;
    }

    public function isInputObjectType(): bool
    {
        return $this instanceof GraphQLInputObjectType;
    }

    public function isIntType(): bool
    {
        return $this instanceof GraphQLInt;
    }

    public function isInterfaceType(): bool
    {
        return $this instanceof GraphQLInterface;
    }

    public function isListType(): bool
    {
        return $this instanceof GraphQLList;
    }

    public function isNonNullType(): bool
    {
        return $this instanceof GraphQLNonNull;
    }

    public function isObjectType(): bool
    {
        return $this instanceof GraphQLObjectType;
    }

    public function isStringType(): bool
    {
        return $this instanceof GraphQLString;
    }

    public function isUnionType(): bool
    {
        return $this instanceof GraphQLUnion;
    }

    public function isScalarType(): bool
    {
        return $this instanceof GraphQLScalarType;
    }

    public function isWrappingType(): bool
    {
        return ($this->isListType() || $this->isNonNullType());
    }

    public function isLeafType(): bool
    {
        return ($this->isScalarType() || $this->isEnumType());
    }

    public function isCompositeType(): bool
    {
        return ($this->isObjectType() || $this->isInterfaceType() || $this->isUnionType());
    }

    public function isInputType(): bool
    {
        return ($this->isScalarType() ||
            $this->isEnumType() ||
            $this->isInputObjectType() ||
            ($this->isWrappingType() and $this->getInnerType()->isInputType()));
    }
}