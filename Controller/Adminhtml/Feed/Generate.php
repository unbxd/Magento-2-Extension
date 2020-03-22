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

use Unbxd\ProductFeed\Controller\Adminhtml\ActionIndex;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Unbxd\ProductFeed\Model\BackgroundTaskManager;
use Unbxd\ProductFeed\Console\Command\Feed\Download;
use Unbxd\ProductFeed\Model\CacheManager;

/**
 * Class Generate
 * @package Unbxd\ProductFeed\Controller\Adminhtml\Feed
 */
class Generate extends ActionIndex
{
    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $this->flushCache();

        // mark for download to display message notification after the feed is generated
        $this->setIsGeneratedForDownload(true);

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $responseContent = [];
        $resultJson->setData($responseContent);

        $isValid = $this->_isValidPostRequest();
        if (!$isValid) {
            $this->messageManager->addErrorMessage(__('Invalid request for product feed generation.'));
            return $resultJson;
        }

        $storeId = $this->getCurrentStoreId();
        $storeName = $this->getStore($storeId)->getName();
        try {
            /** @var BackgroundTaskManager $backgroundTaskManager */
            $backgroundTaskManager = $this->backgroundTaskManagerFactory->create();
            $backgroundTaskManager->execute([Download::COMMAND], $storeId);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Unable to generate product feed. Error %1', $e->getMessage()));
            return $resultJson;
        }

        $this->messageManager->addSuccessMessage(__('Product feed generation for store with ID %1 (%2) was started. 
            Generating may take some time depending on the catalog size. Once the product feed is generated 
            you will be able to download it as an archive file in ZIP format.', $storeId, $storeName)
        );

        return $resultJson;
    }

    /**
     * Flush cache by types before execute
     *
     * @return $this
     */
    private function flushCache()
    {
        /** @var CacheManager $cacheManager */
        $cacheManager = $this->cacheManagerFactory->create();
        $cacheManager->flushByTypes();

        return $this;
    }
}