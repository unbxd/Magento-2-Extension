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
namespace Unbxd\ProductFeed\Model\Indexer\Product\Full\DataSourceProvider\Price;

use Unbxd\ProductFeed\Model\Indexer\Product\Full\DataSourceProvider\Price\PriceReaderInterface;

/**
 * Price data parser used for grouped and bundled products.
 *
 * Class Grouped
 * @package Unbxd\ProductFeed\Model\Indexer\Product\Full\DataSourceProvider\Price
 */
class Grouped implements PriceReaderInterface
{
    /**
     * {@inheritDoc}
     */
    public function getPrice($priceData)
    {
        return isset($priceData['min_price']) ? $priceData['min_price'] : 0;
    }

    /**
     * {@inheritDoc}
     */
    public function getOriginalPrice($priceData)
    {
        return isset($priceData['min_price']) ? $priceData['min_price'] : 0;
    }

    /**
     * {@inheritDoc}
     */
    public function getSpecialPrice($priceData)
    {
        return isset($priceData['min_price']) ? $priceData['min_price'] : 0;
    }
}
