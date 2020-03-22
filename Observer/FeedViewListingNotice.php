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
namespace Unbxd\ProductFeed\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Unbxd\ProductFeed\Observer\AbstractObserver;

/**
 * Class FeedViewListingNotice
 * @package Unbxd\ProductFeed\Observer
 */
class FeedViewListingNotice extends AbstractObserver implements ObserverInterface
{
    /**
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        if (!$this->helperData->isAuthorizationCredentialsSetup()) {
            $this->messageManager->addWarningMessage($this->getAuthorizationCredentialsAreNotSetupMessage());
        }

        if (!$this->helperData->isGeneralCronConfigured()) {
            $this->messageManager->addWarningMessage($this->getGeneralCronIsNotConfiguredMessage());
        }

        return $this;
    }
}
