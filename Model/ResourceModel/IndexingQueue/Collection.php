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
namespace Unbxd\ProductFeed\Model\ResourceModel\IndexingQueue;

use Unbxd\ProductFeed\Model\ResourceModel\AbstractCollection;
use Unbxd\ProductFeed\Model\IndexingQueue;
use Unbxd\ProductFeed\Model\ResourceModel\IndexingQueue as ResourceModelIndexingQueue;
use Unbxd\ProductFeed\Api\Data\IndexingQueueInterface;

/**
 * Class Collection
 * @package Unbxd\ProductFeed\Model\ResourceModel\IndexingQueue
 */
class Collection extends AbstractCollection
{
    /**
     * ID field name
     *
     * @var string
     */
    protected $_idFieldName = 'queue_id';

    /**
     * Event prefix
     *
     * @var string
     */
    protected $_eventPrefix = 'unbxd_productfeed_indexing_queue_collection';

    /**
     * Event object
     *
     * @var string
     */
    protected $_eventObject = 'indexing_queue_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(IndexingQueue::class, ResourceModelIndexingQueue::class);
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
