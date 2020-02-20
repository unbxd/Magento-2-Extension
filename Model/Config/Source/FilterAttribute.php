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
namespace Unbxd\ProductFeed\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Unbxd\ProductFeed\Model\FilterAttribute\FilterAttributeProvider;

/**
 * Class FilterAttribute
 * @package Unbxd\ProductFeed\Model\Config\Source
 */
class FilterAttribute implements ArrayInterface
{
    /**
     * Constant to check whether product must be filtering by specific attribute value or not
     */
    const DON_NOT_EXCLUDE_KEY = 'all';

    /**
     * Flag to detect whether add all products without exclude or not
     *
     * @var bool
     */
    protected $doNotExcludeProducts = true;

    /**
     * @var FilterAttributeProvider
     */
    protected $filterAttributeProvider;

    /**
     * FilterAttribute constructor.
     * @param FilterAttributeProvider $filterAttributeProvider
     */
    public function __construct(
        FilterAttributeProvider $filterAttributeProvider
    ) {
        $this->filterAttributeProvider = $filterAttributeProvider;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $attributes = [];
        foreach ($this->filterAttributeProvider->getList() as $attribute) {
            $attributes[$attribute->getAttributeCode()] = $attribute->getLabel();
        }

        if ($this->doNotExcludeProducts && !array_key_exists(self::DON_NOT_EXCLUDE_KEY, $attributes)) {
            $attributes = [self::DON_NOT_EXCLUDE_KEY => __('DON\'T EXCLUDE')] + $attributes;
        }

        return $attributes;
    }

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        $optionArray = [];
        $arr = $this->toArray();
        foreach ($arr as $value => $label) {
            $optionArray[] = [
                'value' => $value,
                'label' => $label
            ];
        }

        return $optionArray;
    }
}