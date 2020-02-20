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
namespace Unbxd\ProductFeed\Model\FilterAttribute\Attributes;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Visibility as ProductVisibility;
use Unbxd\ProductFeed\Model\FilterAttribute\FilterAttributeInterface;

/**
 * Class Visibility
 * @package Unbxd\ProductFeed\Model\FilterAttribute\Attributes
 */
class Visibility implements FilterAttributeInterface
{
    /**
     * Constant for attribute code
     */
    const ATTRIBUTE_CODE = ProductInterface::VISIBILITY;

    /**
     * @var ProductVisibility
     */
    protected $productVisibility;

    /**
     * Visibility constructor.
     * @param ProductVisibility $productVisibility
     */
    public function __construct(
        ProductVisibility $productVisibility
    ) {
        $this->productVisibility = $productVisibility;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeCode()
    {
        return self::ATTRIBUTE_CODE;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue()
    {
        return ProductVisibility::VISIBILITY_NOT_VISIBLE;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        $label = __('Not Visible Individually');
        $options = $this->productVisibility->toOptionArray();
        foreach ($options as $option) {
            $value = isset($option['value']) ? $option['value'] : null;
            $label = isset($option['label']) ? $option['label'] : null;
            if ($value && ($value == $this->getValue())) {
                return $label;
            }
        }

        return $label;
    }
}