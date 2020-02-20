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
namespace Unbxd\ProductFeed\Controller\Adminhtml\Feed\View;

use Unbxd\ProductFeed\Api\Data\IndexingQueueInterface;
use Unbxd\ProductFeed\Controller\Adminhtml\ActionIndex;
use Magento\Framework\Controller\ResultFactory;
use Unbxd\ProductFeed\Model\FeedView;
use Unbxd\ProductFeed\Model\Feed\Config as FeedConfig;
use Unbxd\ProductFeed\Model\IndexingQueue;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Repeat
 * @package Unbxd\ProductFeed\Controller\Adminhtml\Feed\View
 */
class Repeat extends ActionIndex
{
    /**
     * @return \Magento\Backend\Model\View\Result\Redirect|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        $id = $this->getRequest()->getParam('id');
        // check if we know what should be repeated
        if ($id) {
            try {
                /** @var \Unbxd\ProductFeed\Api\Data\FeedViewInterface $model */
                $model = $this->feedViewRepository->getById($id);
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage(__('This feed view no longer exists.'));
                return $resultRedirect->setRefererUrl();
            }

            // check if the operation is available for repeat
            if ($model->getStatus() != FeedView::STATUS_ERROR) {
                $this->messageManager->addErrorMessage(__('Only operation in \'Error\' status can be repeated.'));
                return $resultRedirect->setRefererUrl();
            }
            if ($model->getNumberOfAttempts() >= $this->helperData->getMaxNumberOfAttempts()) {
                $configUrl = $this->getUrl('adminhtml/system_config/edit/section/unbxd_catalog');
                $message = sprintf(
                    'Limit exceeded for this operation. You can adjust the max number of attempts in <a href="%s">Configuration</a> section.',
                    $configUrl
                );
                // \Magento\Framework\Message\ManagerInterface::addErrorMessage method strip HTML tags
                $this->messageManager->addError(__($message));
                return $resultRedirect->setRefererUrl();
            }

            if ($this->prepareNewJobForIndexing($model)) {
                $this->deleteCurrentOperation($model->getId());
            }
        }

        return $resultRedirect->setRefererUrl();
    }

    /**
     * @param \Unbxd\ProductFeed\Api\Data\FeedViewInterface $operationModel
     * @return bool
     */
    private function prepareNewJobForIndexing($operationModel)
    {
        $isFullCatalogAffected = (bool) ($operationModel->getOperationTypes() == FeedConfig::FEED_TYPE_FULL);
        $entityIds = $isFullCatalogAffected
            ? []
            : $this->queueHandler->convertStringToIds($operationModel->getAffectedEntities());
        $actionType = empty($entityIds) ? IndexingQueue::TYPE_REINDEX_FULL : '';

        try {
            $this->queueHandler->add($entityIds, $actionType, $operationModel->getStoreId(),
                [
                    IndexingQueueInterface::NUMBER_OF_ATTEMPTS => $operationModel->getNumberOfAttempts()
                ]
            );
            $indexingQueueUrl = $this->getUrl('unbxd_productfeed/indexing/queue');
            // \Magento\Framework\Message\ManagerInterface::addSuccessMessage method strip HTML tags
            $this->messageManager->addSuccess(
                __('The new operation was added to the <a href="%1">Indexing Queue</a>.', $indexingQueueUrl)
            );
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __('Something went wrong when try to create new indexing operation. %s', $e->getMessage())
            );
            return false;
        }

        return true;
    }

    /**
     * @param $operationId
     * @return $this
     */
    private function deleteCurrentOperation($operationId)
    {
        try {
            $this->feedViewRepository->deleteById($operationId);
            $this->messageManager->addSuccessMessage(__('Feed view record #%1 was deleted.', $operationId));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while deleting feed view.'));
        }

        return $this;
    }
}