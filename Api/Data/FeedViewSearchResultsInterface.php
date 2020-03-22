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
 * Interface for feed view search results.
 *
 * Interface FeedViewSearchResultsInterface
 * @package Unbxd\ProductFeed\Api\Data
 */
interface FeedViewSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get feed view list.
     *
     * @return \Unbxd\ProductFeed\Api\Data\FeedViewInterface[]
     */
    public function getItems();

    /**
     * Set feed view list.
     *
     * @param \Unbxd\ProductFeed\Api\Data\FeedViewInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
