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
 * Class DeleteAll
 * @package Unbxd\ProductFeed\Controller\Adminhtml\Indexing\Queue
 */
class DeleteAll extends ActionIndex
{
    /**
     * @return \Magento\Backend\Model\View\Result\Redirect|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Unbxd\ProductFeed\Model\ResourceModel\IndexingQueue\Collection $collection */
        $collection = $this->indexingQueueCollectionFactory->create();
        $collectionSize = $collection->getSize();
        if ($collectionSize) {
            $skipRunning = [];
            $proceeded = [];
            foreach ($collection as $item) {
                $id = $item->getId();
                $currentStatus = $item->getStatus();
                if ($currentStatus == IndexingQueue::STATUS_RUNNING) {
                    array_push($skipRunning, "#" . $id);
                    continue;
                }
                $item->delete();
                $proceeded[] = $id;
            }

            if (!empty($skipRunning)) {
                $this->messageManager->addErrorMessage(__(
                    '%1 in \'Running\' status can\'t be deleted.', implode(', ', $skipRunning))
                );
            }

            if ($proceededSize = count($proceeded)) {
                $this->messageManager->addSuccessMessage(__(
                    'A total of %1 record(s) have been deleted.', $proceededSize)
                );
            }
        }

        if (!$collectionSize) {
            $this->messageManager->addSuccessMessage(__('Nothing to clear. Queue list are empty.'));
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setRefererUrl();
    }
}