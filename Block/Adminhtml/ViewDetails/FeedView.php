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
namespace Unbxd\ProductFeed\Block\Adminhtml\ViewDetails;

use Unbxd\ProductFeed\Block\Adminhtml\ViewDetails;
use Unbxd\ProductFeed\Model\FeedView as FeedViewModel;

/**
 * Class FeedView
 * @package Unbxd\ProductFeed\Block\Adminhtml\ViewDetails
 */
class FeedView extends ViewDetails
{
    /**
     * Retrieve current queue item instance
     *
     * @return FeedViewModel
     */
    public function getItem()
    {
        return $this->registry->registry('feed_view_item');
    }
}