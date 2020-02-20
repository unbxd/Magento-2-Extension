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
namespace Unbxd\ProductFeed\Controller\Adminhtml\Indexing\Queue;

use Unbxd\ProductFeed\Controller\Adminhtml\ActionIndex;
use Magento\Framework\Controller\ResultFactory;
use Unbxd\ProductFeed\Model\IndexingQueue;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Delete
 * @package Unbxd\ProductFeed\Controller\Adminhtml\Indexing\Queue
 */
class Delete extends ActionIndex
{
    /**
     * @return \Magento\Backend\Model\View\Result\Redirect|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        $id = $this->getRequest()->getParam('id');
        // check if we know what should be deleted
        if ($id) {
            try {
                /** @var \Unbxd\ProductFeed\Api\Data\IndexingQueueInterface $model */
                $model = $this->indexingQueueRepository->getById($id);
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage(__('This queue item no longer exists.'));
                return $resultRedirect->setRefererUrl();
            }

            $currentStatus = $model->getStatus();
            if ($currentStatus == IndexingQueue::STATUS_RUNNING) {
                $this->messageManager->addErrorMessage(__('Queue item in \'Running\' status can\'t be deleted.'));
                return $resultRedirect->setRefererUrl();
            }

            try {
                $this->indexingQueueRepository->delete($model);
                $this->messageManager->addSuccessMessage(__('Queue item was deleted.'));
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while deleting queue item.'));
            }
        }

        return $resultRedirect->setRefererUrl();
    }
}