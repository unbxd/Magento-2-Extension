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

/**
 * Definition of "filter attributes" that can be used for filter product collection
 *
 * Interface FilterAttributeInterface
 * @package Unbxd\ProductFeed\Model\FilterAttribute
 */
interface FilterAttributeInterface
{
    /**
     * @return string
     */
    public function getAttributeCode();

    /**
     * @return string
     */
    public function getLabel();

    /**
     * @return string|mixed|bool|null
     */
    public function getValue();
}