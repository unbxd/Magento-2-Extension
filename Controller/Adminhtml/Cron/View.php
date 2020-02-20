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
namespace Unbxd\ProductFeed\Controller\Adminhtml\Cron;

use Unbxd\ProductFeed\Controller\Adminhtml\ViewIndex;
use Magento\Framework\Controller\ResultFactory;
use Unbxd\ProductFeed\Block\Adminhtml\AdditionalToolbar;

/**
 * Class Check
 * @package Unbxd\ProductFeed\Controller\Adminhtml\Cron
 */
class View extends ViewIndex
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Unbxd_ProductFeed::cron';

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Unbxd_ProductFeed::cron');
        $resultPage->getConfig()->getTitle()->prepend(__('Unbxd | Related Cron Jobs'));

        // set init parameters for additional toolbar
        $resultPage->getLayout()->getBlock('additional.toolbar')->setListingView(
            AdditionalToolbar::ITEM_RELATED_CRON_JOBS
        )->setCurrentItemKey(
            AdditionalToolbar::ITEM_SETUP
        );

        return $resultPage;
    }
}