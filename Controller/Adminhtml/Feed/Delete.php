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
use Magento\Framework\App\Filesystem\DirectoryList;
use Unbxd\ProductFeed\Model\Feed\FileManager as FeedFileManager;

/**
 * Class Delete
 * @package Unbxd\ProductFeed\Controller\Adminhtml\Feed
 */
class Delete extends ActionIndex
{
    /**
     * @return \Magento\Backend\Model\View\Result\Redirect|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        /** @var \Unbxd\ProductFeed\Model\Feed\FileManager $feedFileManager */
        $feedFileManager = $this->feedFileManagerFactory->create(
            [
                'subDir' => FeedFileManager::DEFAULT_SUB_DIR_FOR_DOWNLOAD,
                'store' => sprintf('%s%s', FeedFileManager::STORE_PARAMETER, $this->getCurrentStoreId())
            ]
        );
        $feedFileManager->setIsConvertedToArchive(true);
        if (!$feedFileManager->isExist()) {
            $this->messageManager->addErrorMessage(
                __('There are no product feed files for current store available for delete.')
            );
            return $resultRedirect->setRefererUrl();
        }

        try {
            $feedFileManager->deleteAffectedFiles();
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Unable to delete product feed files for current store.'));
        }

        $this->messageManager->addSuccessMessage(__('Product feed files for current store were successfully deleted.'));

        return $resultRedirect->setRefererUrl();
    }
}