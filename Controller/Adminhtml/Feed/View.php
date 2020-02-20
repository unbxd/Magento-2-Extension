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
namespace Unbxd\ProductFeed\Controller\Adminhtml\Feed;

use Unbxd\ProductFeed\Controller\Adminhtml\ViewIndex;
use Magento\Framework\Controller\ResultFactory;
use Unbxd\ProductFeed\Block\Adminhtml\AdditionalToolbar;

/**
 * Class Index
 * @package Unbxd\ProductFeed\Controller\Adminhtml\Feed\View
 */
class View extends ViewIndex
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
        $resultPage->setActiveMenu('Unbxd_ProductFeed::productfeed');
        $resultPage->addBreadcrumb(__('Product Feed'), __('Product Feed'));
        $resultPage->addBreadcrumb(__('Log View'), __('Log View'));
        $resultPage->getConfig()->getTitle()->prepend(__('Unbxd | Feed View'));

        // set init parameters for additional toolbar
        $resultPage->getLayout()->getBlock('additional.toolbar')->setListingView(
            AdditionalToolbar::ITEM_FEED_VIEW
        )->setCurrentItemKey(
            AdditionalToolbar::ITEM_SETUP
        );

        return $resultPage;
    }
}