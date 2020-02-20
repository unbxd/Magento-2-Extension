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
namespace Unbxd\ProductFeed\Model\Indexer\Product\Full;

/**
 * Interface DataSourceProviderInterface
 * @package Unbxd\ProductFeed\Model\Indexer\Product\Full
 */
interface DataSourceProviderInterface
{
    /**
     * Return related data source code
     *
     * @return string
     */
    public function getDataSourceCode();

    /**
     * Append data to a list for indexation
     *
     * @param $storeId
     * @param array $indexData
     * @return mixed
     */
    public function appendData($storeId, array $indexData);
}
