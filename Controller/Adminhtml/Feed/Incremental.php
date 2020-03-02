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

use Unbxd\ProductFeed\Controller\Adminhtml\FeedActionIndex;
use Magento\Framework\Controller\ResultFactory;
use Unbxd\ProductFeed\Model\FeedView;
use Unbxd\ProductFeed\Model\Feed\Config as FeedConfig;

/**
 * Class Incremental
 * @package Unbxd\ProductFeed\Controller\Adminhtml\Feed
 */
class Incremental extends FeedActionIndex
{
    /**
     * @return array|bool|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        $responseContent = [];
        $isValid = $this->isValidPostRequest();
        if (!$isValid) {
            $responseContent = [
                'errors' => true,
                'message' => __('Invalid request.')
            ];
            $resultJson->setData($responseContent);
            return $resultJson;
        }

        $productIds = trim($this->getRequest()->getParam('ids'));
        if ($productIds) {
            $productIds = explode(',', $productIds);
            if (!empty($productIds)) {
                try {
                    $index = $this->reindexAction->rebuildProductStoreIndex($this->getCurrentStoreId(), $productIds);
                } catch (\Exception $e) {
                    $responseContent = [
                        'errors' => true,
                        'message' => __('Indexing error: %s', $e->getMessage())
                    ];
                    $resultJson->setData($responseContent);
                    return $resultJson;
                }

                try {
                    $this->feedManager->execute($index, FeedConfig::FEED_TYPE_INCREMENTAL);
                } catch (\Exception $e) {
                    $responseContent = [
                        'errors' => true,
                        'message' => __(__('Feed execution error: %s', $e->getMessage()))
                    ];
                    $resultJson->setData($responseContent);
                    return $resultJson;
                }
            }
        }

        $this->buildResponseMessage();

        $resultJson->setData($responseContent);
        return $resultJson;
    }

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function buildResponseMessage()
    {
        $viewDetailsLink = sprintf(
            '<a href="%s">' . __('View details') . '</a>',
            $this->getUrl('unbxd_productfeed/feed/view')
        );
        // \Magento\Framework\Message\ManagerInterface::addSuccessMessage strip HTML tags
        if ($this->feedHelper->isLastSynchronizationProcessing()) {
            $message = sprintf(FeedConfig::FEED_MESSAGE_BY_RESPONSE_TYPE_INDEXING . ' %s', $viewDetailsLink);
            $this->messageManager->addSuccess(__($message));
        } else if ($this->feedHelper->isLastSynchronizationSuccess()) {
            $message = sprintf(FeedConfig::FEED_MESSAGE_BY_RESPONSE_TYPE_COMPLETE . ' %s', $viewDetailsLink);
            $this->messageManager->addSuccess(__($message));
        } else {
            $message = sprintf(
                FeedConfig::FEED_MESSAGE_BY_RESPONSE_TYPE_ERROR . ' %s',
                $this->getStore()->getId(),
                $viewDetailsLink
            );
            $this->messageManager->addError(__($message));
        }

        return $this;
    }
}