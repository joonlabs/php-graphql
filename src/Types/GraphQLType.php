<?php

namespace GraphQL\Types;

use GraphQL\Fields\GraphQLQueryField;
use GraphQL\Resolvers\QueryResolver;
use GraphQL\Variables\GraphQLVariableHolder;

/**
 * Class GraphQLType
 * @package GraphQL\Types
 */
abstract class GraphQLType
{
    protected $type;
    protected $description;

    /**
     * @return string
     */
    public function getName(): string
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

    /**
     * @return $this
     */
    public function getNamedType(): GraphQLType
    {
        if ($this instanceof GraphQLNonNull or $this instanceof GraphQLList) {
            return $this->getInnerType()->getNamedType();
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function isAbstractType(): bool
    {
        return $this instanceof GraphQLAbstractType;
    }

    /**
     * @return bool
     */
    public function isBooleanType(): bool
    {
        return $this instanceof GraphQLBoolean;
    }

    /**
     * @return bool
     */
    public function isEnumType(): bool
    {
        return $this instanceof GraphQLEnum;
    }

    /**
     * @return bool
     */
    public function isFloatType(): bool
    {
        return $this instanceof GraphQLFloat;
    }

    /**
     * @return bool
     */
    public function isIDType(): bool
    {
        return $this instanceof GraphQLID;
    }

    /**
     * @return bool
     */
    public function isInputObjectType(): bool
    {
        return $this instanceof GraphQLInputObjectType;
    }

    /**
     * @return bool
     */
    public function isIntType(): bool
    {
        return $this instanceof GraphQLInt;
    }

    /**
     * @return bool
     */
    public function isInterfaceType(): bool
    {
        return $this instanceof GraphQLInterface;
    }

    /**
     * @return bool
     */
    public function isListType(): bool
    {
        return $this instanceof GraphQLList;
    }

    /**
     * @return bool
     */
    public function isNonNullType(): bool
    {
        return $this instanceof GraphQLNonNull;
    }

    /**
     * @return bool
     */
    public function isObjectType(): bool
    {
        return $this instanceof GraphQLObjectType and !($this instanceof GraphQLInputObjectType);
    }

    /**
     * @return bool
     */
    public function isStringType(): bool
    {
        return $this instanceof GraphQLString;
    }

    /**
     * @return bool
     */
    public function isUnionType(): bool
    {
        return $this instanceof GraphQLUnion;
    }

    /**
     * @return bool
     */
    public function isScalarType(): bool
    {
        return $this instanceof GraphQLScalarType;
    }

    /**
     * @return bool
     */
    public function isWrappingType(): bool
    {
        return ($this->isListType() || $this->isNonNullType());
    }

    /**
     * @return bool
     */
    public function isLeafType(): bool
    {
        return ($this->isScalarType() || $this->isEnumType());
    }

    /**
     * @return bool
     */
    public function isCompositeType(): bool
    {
        return ($this->isObjectType() || $this->isInterfaceType() || $this->isUnionType());
    }

    /**
     * @return bool
     */
    public function isInputType(): bool
    {
        return ($this->isScalarType() ||
            $this->isEnumType() ||
            $this->isInputObjectType() ||
            ($this->isWrappingType() and $this->getInnerType()->isInputType()));
    }
}