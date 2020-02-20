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
namespace Unbxd\ProductFeed\Block\Adminhtml\ViewDetails\Tab\Products;

use Unbxd\ProductFeed\Block\Adminhtml\ViewDetails\Tab\Products;
use Unbxd\ProductFeed\Model\IndexingQueue as IndexingQueueModel;

/**
 * Class Products
 * @package Unbxd\ProductFeed\Block\Adminhtml\ViewDetails\Tab
 */
class IndexingQueue extends Products
{
    /**
     * Retrieve current item instance
     *
     * @return IndexingQueueModel
     */
    public function getItem()
    {
        return $this->registry->registry('indexing_queue_item');
    }

    /**
     * @return bool|mixed
     */
    public function isFullCatalog()
    {
        return (bool) ($this->getItem()->getActionType() == IndexingQueueModel::TYPE_REINDEX_FULL);
    }
}