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
 * Class Unhold
 * @package Unbxd\ProductFeed\Controller\Adminhtml\Indexing\Queue
 */
class Unhold extends ActionIndex
{
    /**
     * @return \Magento\Backend\Model\View\Result\Redirect|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        $id = $this->getRequest()->getParam('id');
        // check if we know what should be unholded
        if ($id) {
            try {
                /** @var \Unbxd\ProductFeed\Api\Data\IndexingQueueInterface $model */
                $model = $this->indexingQueueRepository->getById($id);
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage(__('This queue item no longer exists.'));
                return $resultRedirect->setRefererUrl();
            }

            // check if item can be put on hold
            $currentStatus = $model->getStatus();
            if ($currentStatus != IndexingQueue::STATUS_HOLD) {
                $this->messageManager->addErrorMessage(__('Only queue item on \'Hold\' status can be released.'));
                return $resultRedirect->setRefererUrl();
            }

            $model->setStatus(IndexingQueue::STATUS_PENDING);

            try {
                $this->indexingQueueRepository->save($model);
                $this->messageManager->addSuccessMessage(__(
                    'Queue item #%1 was released from on \'Hold\' status.', $model->getId())
                );
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving queue item.'));
            }
        }

        return $resultRedirect->setRefererUrl();
    }
}