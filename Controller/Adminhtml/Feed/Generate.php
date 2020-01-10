<?php
/**
 * Copyright (c) 2019 Unbxd Inc.
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

/**
 * Class Generate
 * @package Unbxd\ProductFeed\Controller\Adminhtml\Feed
 */
class Generate extends ActionIndex
{
    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $this->setDefaultParameters();

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $responseContent = [];
        $resultJson->setData($responseContent);

        $isValid = $this->_isValidPostRequest();
        if (!$isValid) {
            $this->messageManager->addErrorMessage(__('Invalid request for product feed generation.'));
            return $resultJson;
        }

        $this->messageManager->addSuccessMessage(__('Product feed generation was started. Generating may take
            some time depending on the catalog size. Once the product feed is generated you will be able
            to download it as an archive file in ZIP format.')
        );

        /** @var \Unbxd\ProductFeed\Model\Indexer\Product\Full\Action\Full $reindexAction */
        $reindexAction = $this->reindexActionFactory->create();
        try {
            $index = $reindexAction->rebuildProductStoreIndex($this->getStore()->getId(), []);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Unable to build product store index.'));
            return $resultJson;
        }

        if (empty($index)) {
            $this->messageManager->addErrorMessage(__('Product store index is empty.'));
            return $resultJson;
        }

        $this->generateProductFeed($index);

        return $resultJson;
    }

    /**
     * Ignore user aborts and allow the script to run forever
     *
     * @return $this
     */
    private function setDefaultParameters()
    {
        ignore_user_abort(true);
        set_time_limit(0);

        return $this;
    }

    /**
     * @param array $index
     * @return $this
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function generateProductFeed(array $index)
    {
        /** @var \Unbxd\ProductFeed\Model\Feed\Manager $feedManager */
        $feedManager = $this->feedManagerFactory->create();

        $feedManager->prepareData($index);
        $feedManager->buildFeed();
        $feedManager->serializeFeed();
        $feedManager->writeFeed();

        return $this;
    }
}