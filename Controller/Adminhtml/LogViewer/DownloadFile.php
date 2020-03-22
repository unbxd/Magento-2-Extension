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
namespace Unbxd\ProductFeed\Controller\Adminhtml\LogViewer;

use Unbxd\ProductFeed\Controller\Adminhtml\LogViewer;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class DownloadFile
 * @package Unbxd\ProductFeed\Controller\Adminhtml\LogViewer
 */
class DownloadFile extends LogViewer
{
    /**
     * @return \Magento\Backend\Model\View\Result\Redirect|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function execute()
    {
        $type = $this->getRequest()->getParam('type');
        // check if we know which log type need to be retrieved
        if (!$type) {
            /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            $this->messageManager->addErrorMessage(__('Empty request data: log type is required.'));

            return $resultRedirect->setRefererUrl();
        }

        /** @var \Unbxd\ProductFeed\Logger\File $logger */
        $logger = $this->getLogger($type);

        return $this->fileFactory->create($logger->getFileName($type), $logger->getFileContent());
    }
}