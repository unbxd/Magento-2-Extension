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
 * Class Download
 * @package Unbxd\ProductFeed\Controller\Adminhtml\Feed
 */
class Download extends ActionIndex
{
    /**
     * @return \Magento\Backend\Model\View\Result\Redirect|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\FileSystemException
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
            $this->messageManager->addErrorMessage(__('There are no product feed files available for download.'));
            return $resultRedirect->setRefererUrl();
        }

        $archiveFormat = $feedFileManager->getArchiveFormat();
        $fileName = str_replace(
            '.' . $archiveFormat,
            sprintf(
                '%s%s_%s.%s',
                FeedFileManager::STORE_PARAMETER,
                $this->getCurrentStoreId(),
                date('Y-m-d-H-i-s', $feedFileManager->getFileMtime()),
                $archiveFormat
            ),
            $feedFileManager->getFileName()
        );

        // mark as downloaded to prevent display message notification
        $this->setIsGeneratedForDownload(false);

        $fileContent = ['type' => 'filename', 'value' => $feedFileManager->getFileLocation()];

        return $this->fileFactory->create(
            $fileName,
            $fileContent,
            DirectoryList::VAR_DIR,
            $feedFileManager->getMimeType(),
            $feedFileManager->getFileSize()
        );
    }
}