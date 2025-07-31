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
 * Class SystemConfigCronNotice
 * @package Unbxd\ProductFeed\Observer
 */
class SystemConfigNotice extends AbstractObserver implements ObserverInterface
{

    /**
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        $storeId= $observer->getRequest()->getParam('store');
        $section = $observer->getRequest()->getParam('section');
        
        $warningMessages = [];
        if(!$storeId){
            return $this;
        }
        if (
            ($section == self::SYSTEM_CONFIG_SECTION_PARAM_UNBXD_SETUP)
            && !$this->helperData->isAuthorizationCredentialsSetup($storeId)
        ) {
            $warningMessages[] = $this->getAuthorizationCredentialsAreNotSetupMessage();
        }

        if ($section == self::SYSTEM_CONFIG_SECTION_PARAM_UNBXD_CATALOG) {
            if (!$this->helperData->isGeneralCronConfigured($storeId)) {
                $warningMessages[] = $this->getGeneralCronIsNotConfiguredMessage();
            }
            if (!$this->helperData->isIndexingQueueEnabled($storeId)) {
                $warningMessages[] = $this->getIndexingQueueIsDisabledMessage();
            }
        }

        if (!empty($warningMessages)) {
            foreach ($warningMessages as $message) {
                $this->messageManager->addWarningMessage($message);
            }
        }

        return $this;
    }
}
