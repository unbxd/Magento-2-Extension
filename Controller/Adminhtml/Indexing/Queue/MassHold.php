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
 * Class MassHold
 * @package Unbxd\ProductFeed\Controller\Adminhtml\Indexing\Queue
 */
class MassHold extends ActionIndex
{
    /**
     * @return \Magento\Backend\Model\View\Result\Redirect|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws LocalizedException
     */
    public function execute()
    {
        /** @var \Unbxd\ProductFeed\Model\ResourceModel\IndexingQueue\Collection $collection */
        $collection = $this->indexingQueueCollectionFactory->create();
        $collection = $this->massActionFilter->getCollection($collection);

        $skipAlreadyOnHold = [];
        $skipNotPending = [];
        $proceeded = [];
        foreach ($collection as $item) {
            $id = $item->getId();
            $currentStatus = $item->getStatus();
            if ($currentStatus == IndexingQueue::STATUS_HOLD) {
                array_push($skipAlreadyOnHold, "#" . $id);
                continue;
            }
            if ($currentStatus != IndexingQueue::STATUS_PENDING) {
                array_push($skipNotPending, "#" . $id);
                continue;
            }
            $item->setStatus(IndexingQueue::STATUS_HOLD);
            $this->save($item);
            $proceeded[] = $id;
        }

        if (!empty($skipAlreadyOnHold)) {
            $this->messageManager->addErrorMessage(__(
                '%1 already on \'Hold\' status.', implode(', ', $skipAlreadyOnHold))
            );
        }

        if (!empty($skipNotPending)) {
            $this->messageManager->addErrorMessage(__(
                '%1 are not in \'Pending\' status.', implode(', ', $skipNotPending))
            );
        }

        if ($proceededSize = count($proceeded)) {
            $this->messageManager->addSuccessMessage(__(
                'A total of %1 record(s) have been put on \'Hold\' status.', $proceededSize)
            );
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setRefererUrl();
    }

    /**
     * @param $queue
     */
    private function save($queue)
    {
        try {
            $this->indexingQueueRepository->save($queue);
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving this item.'));
        }
    }
}