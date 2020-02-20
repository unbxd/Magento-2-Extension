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
 * Default price data parser
 *
 * Class PriceDefault
 * @package Unbxd\ProductFeed\Model\Indexer\Product\Full\DataSourceProvider\Price
 */
class PriceDefault implements PriceReaderInterface
{
    /**
     * {@inheritDoc}
     */
    public function getPrice($priceData)
    {
        return isset($priceData['final_price'])
            ? $priceData['final_price']
            : isset($priceData['price'])
                ? $priceData['price'] : 0;
    }

    /**
     * {@inheritDoc}
     */
    public function getOriginalPrice($priceData)
    {
        return isset($priceData['price']) ? $priceData['price'] : 0;
    }

    /**
     * {@inheritDoc}
     */
    public function getSpecialPrice($priceData)
    {
        return isset($priceData['final_price']) ? $priceData['final_price'] : 0;
    }
}
