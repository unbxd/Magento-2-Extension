<?php
/**
 * Copyright (c) 2020 Unbxd Inc.
 */

/**
 * Init development:
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 */
namespace Unbxd\ProductFeed\Model\FilterAttribute;

use Unbxd\ProductFeed\Model\FilterAttribute\FilterAttributeInterface;

/**
 * Class FilterAttributeProvider
 * @package Unbxd\ProductFeed\Model\FilterAttribute
 */
class FilterAttributeProvider
{
    /**
     * @var FilterAttributeInterface[]
     */
    private $attributes = [];

    /**
     * FilterAttributeProvider constructor.
     * @param FilterAttributeInterface[] $attributes
     */
    public function __construct($attributes = [])
    {
        $this->attributes = $attributes;
    }

    /**
     * Retrieve filter attributes list.
     *
     * @return FilterAttributeInterface[]
     */
    public function getList()
    {
        return $this->attributes;
    }

    /**
     * Retrieve a filter attribute by code.
     *
     * @param $attributeCode
     * @return FilterAttributeInterface|null
     */
    public function getAttribute($attributeCode)
    {
        return isset($this->attributes[$attributeCode]) ? $this->attributes[$attributeCode] : null;
    }
}