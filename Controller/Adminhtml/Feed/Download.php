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

/**
 * Class Full
 * @package Unbxd\ProductFeed\Controller\Adminhtml\Feed
 */
class Download extends ActionIndex
{
    /**
     * @return \Magento\Backend\Model\View\Result\Redirect|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        /** @var \Unbxd\ProductFeed\Model\Feed\FileManager $feedFileManager */
        $feedFileManager = $this->feedFileManagerFactory->create();
        $feedFileManager->setIsConvertedToArchive(true);
        if (!$feedFileManager->isExist()) {
            $this->messageManager->addErrorMessage(__('There are no product feed files available for download.'));
            return $resultRedirect->setRefererUrl();
        }

        $archiveFormat = $feedFileManager->getArchiveFormat();
        $fileName = str_replace(
            '.' . $archiveFormat,
            sprintf('_%s.%s', date('Y-m-d-H-i-s', $feedFileManager->getFileMtime()), $archiveFormat),
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