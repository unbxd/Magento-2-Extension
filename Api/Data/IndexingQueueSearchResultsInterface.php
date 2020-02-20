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
namespace Unbxd\ProductFeed\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface for indexing queue search results.
 *
 * Interface IndexingQueueSearchResultsInterface
 * @package Unbxd\ProductFeed\Api\Data
 */
interface IndexingQueueSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get queues list.
     *
     * @return \Unbxd\ProductFeed\Api\Data\IndexingQueueInterface[]
     */
    public function getItems();

    /**
     * Set queues list.
     *
     * @param \Unbxd\ProductFeed\Api\Data\IndexingQueueInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
