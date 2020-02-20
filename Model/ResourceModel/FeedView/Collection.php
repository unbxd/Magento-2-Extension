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
namespace Unbxd\ProductFeed\Model\ResourceModel\FeedView;

use Unbxd\ProductFeed\Model\ResourceModel\AbstractCollection;
use Unbxd\ProductFeed\Model\FeedView;
use Unbxd\ProductFeed\Model\ResourceModel\FeedView as ResourceModelFeedView;
use Unbxd\ProductFeed\Api\Data\FeedViewInterface;

/**
 * Class Collection
 * @package Unbxd\ProductFeed\Model\ResourceModel\FeedView
 */
class Collection extends AbstractCollection
{
    /**
     * ID field name
     *
     * @var string
     */
    protected $_idFieldName = 'feed_id';

    /**
     * Event prefix
     *
     * @var string
     */
    protected $_eventPrefix = 'unbxd_productfeed_feed_view_collection';

    /**
     * Event object
     *
     * @var string
     */
    protected $_eventObject = 'feed_view_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(FeedView::class, ResourceModelFeedView::class);
    }

    /**
     * Add filter by store
     *
     * @param int|array|\Magento\Store\Model\Store $store
     * @param bool $withAdmin
     * @return $this
     */
    public function addStoreFilter($store, $withAdmin = true)
    {
        $this->performAddStoreFilter($store, $withAdmin);

        return $this;
    }
}
