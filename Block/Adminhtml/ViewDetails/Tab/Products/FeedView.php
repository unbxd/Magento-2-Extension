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
use Unbxd\ProductFeed\Model\FeedView as FeedViewModel;
use Unbxd\ProductFeed\Model\Feed\Config as FeedConfig;

/**
 * Class Products
 * @package Unbxd\ProductFeed\Block\Adminhtml\ViewDetails\Tab
 */
class FeedView extends Products
{
    /**
     * Retrieve current item instance
     *
     * @return FeedViewModel
     */
    public function getItem()
    {
        return $this->registry->registry('feed_view_item');
    }

    /**
     * @return bool|mixed
     */
    public function isFullCatalog()
    {
        return (bool) ($this->getItem()->getOperationTypes() == FeedConfig::OPERATION_TYPE_FULL);
    }
}