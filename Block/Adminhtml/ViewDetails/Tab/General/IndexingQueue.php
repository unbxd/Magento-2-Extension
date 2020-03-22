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
namespace Unbxd\ProductFeed\Block\Adminhtml\ViewDetails\Tab\General;

use Unbxd\ProductFeed\Block\Adminhtml\ViewDetails\Tab\General;
use Unbxd\ProductFeed\Model\IndexingQueue as IndexingQueueModel;

/**
 * Class General
 * @package Unbxd\ProductFeed\Block\Adminhtml\Indexing\Queue\Item\Tab
 */
class IndexingQueue extends General
{
    /**
     * @var string
     */
    protected $_template = 'Unbxd_ProductFeed::view-details/indexing/queue/item/general.phtml';

    /**
     * Retrieve current queue item instance
     *
     * @return IndexingQueueModel
     */
    public function getItem()
    {
        return $this->registry->registry('indexing_queue_item');
    }

    /**
     * @param $id
     * @return \Magento\Framework\Phrase
     */
    public function getStatusLabelById($id)
    {
        $availableStatuses = $this->getItem()->getAvailableStatuses();

        return array_key_exists($id, $availableStatuses) ? $availableStatuses[$id] : __('Undefined');
    }

    /**
     * @param $id
     * @return \Magento\Framework\Phrase
     */
    public function getActionLabelById($id)
    {
        $availableActionType = $this->getItem()->getAvailableActionTypes();

        return array_key_exists($id, $availableActionType) ? $availableActionType[$id] : __('Undefined');
    }
}