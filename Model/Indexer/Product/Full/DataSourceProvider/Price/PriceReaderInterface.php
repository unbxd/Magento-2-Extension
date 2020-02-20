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

/**
 * Price data reader to be implemented for each product type.
 *
 * Interface PriceDataReaderInterface
 * @package Unbxd\ProductFeed\Model\Indexer\Product\Full\DataSourceProvider\Price
 */
interface PriceReaderInterface
{
    /**
     * Read the product price.
     *
     * @param $priceData
     * @return mixed
     */
    public function getPrice($priceData);

    /**
     * Read the product original price.
     *
     * @param $priceData
     * @return mixed
     */
    public function getOriginalPrice($priceData);

    /**
     * Read the product special price.
     *
     * @param $priceData
     * @return mixed
     */
    public function getSpecialPrice($priceData);
}
