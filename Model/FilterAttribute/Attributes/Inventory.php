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

use Magento\CatalogInventory\Api\Data\StockStatusInterface;
use Unbxd\ProductFeed\Model\FilterAttribute\FilterAttributeInterface;

/**
 * Class Inventory
 * @package Unbxd\ProductFeed\Model\FilterAttribute\Attributes
 */
class Inventory implements FilterAttributeInterface
{
    /**
     * Constant for attribute code
     */
    const ATTRIBUTE_CODE = StockStatusInterface::STOCK_STATUS;

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
        return StockStatusInterface::STATUS_OUT_OF_STOCK;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return __('Out Of Stock');
    }
}