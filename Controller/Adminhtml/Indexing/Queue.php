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
namespace Unbxd\ProductFeed\Controller\Adminhtml\Indexing;

use Unbxd\ProductFeed\Controller\Adminhtml\ViewIndex;
use Magento\Framework\Controller\ResultFactory;
use Unbxd\ProductFeed\Block\Adminhtml\AdditionalToolbar;

/**
 * Class Queue
 * @package Unbxd\ProductFeed\Controller\Adminhtml\Indexing
 */
class Queue extends ViewIndex
{
    /**
     * Product feed log view
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Unbxd_ProductFeed::productfeed_indexing_queue');
        $resultPage->addBreadcrumb(__('Indexing Queue'), __('Indexing Queue'));
        $resultPage->addBreadcrumb(__('View'), __('View'));
        $resultPage->getConfig()->getTitle()->prepend(__('Unbxd | Indexing Queue View'));

        // set init parameters for additional toolbar
        $resultPage->getLayout()->getBlock('additional.toolbar')->setListingView(
            AdditionalToolbar::ITEM_INDEXING_QUEUE
        )->setCurrentItemKey(
            AdditionalToolbar::ITEM_SETUP
        );

        return $resultPage;
    }
}