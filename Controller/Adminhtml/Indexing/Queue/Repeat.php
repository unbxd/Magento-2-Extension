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
 * Class Repeat
 * @package Unbxd\ProductFeed\Controller\Adminhtml\Indexing\Queue
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
                /** @var \Unbxd\ProductFeed\Api\Data\IndexingQueueInterface $model */
                $model = $this->indexingQueueRepository->getById($id);
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage(__('This queue item no longer exists.'));
                return $resultRedirect->setRefererUrl();
            }

            // check if the operation is available for repeat
            if ($model->getStatus() != IndexingQueue::STATUS_ERROR) {
                $this->messageManager->addErrorMessage(__('Only queue item in \'Error\' status can be switched in \'Pending\' status.'));
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

            $model->setStartedAt('')
                ->setFinishedAt('')
                ->setExecutionTime(0)
                ->setStatus(IndexingQueue::STATUS_PENDING)
                ->setAdditionalInformation('');

            try {
                $this->indexingQueueRepository->save($model);
                $this->messageManager->addSuccessMessage(__(
                    'You have switched queue item #%1 in \'Pending\' status.', $model->getId())
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