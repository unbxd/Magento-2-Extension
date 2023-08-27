<?php
/**
 * Copyright (c) 2020 Unbxd Inc.
 */

/**
 * Init development:
 * @author jags
 * @email jagadeesh@oceaniasolution.com
 * @team Oceania Software Solutions
 */
namespace Unbxd\ProductFeed\Model\Indexer\Product\Full;

/**
 * Interface ContentDataSourceProviderInterface
 * @package Unbxd\ProductFeed\Model\Indexer\Product\Full
 */
interface ContentDataSourceProviderInterface
{

    /**
     * Returns any content list data to be indexed
     *
     * @param $storeId
     * @param $isIncremental
     * @return mixed
     */
    public function getData($storeId,$isIncremental);
}
