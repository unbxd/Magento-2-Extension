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
use Unbxd\ProductFeed\Model\FeedView as FeedViewModel;

/**
 * Class General
 * @package Unbxd\ProductFeed\Block\Adminhtml\Indexing\Queue\Item\Tab
 */
class FeedView extends General
{
    /**
     * @var string
     */
    protected $_template = 'Unbxd_ProductFeed::view-details/feed/view/item/general.phtml';

    /**
     * Retrieve current queue item instance
     *
     * @return FeedViewModel
     */
    public function getItem()
    {
        return $this->registry->registry('feed_view_item');
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
}