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
 * Class RefreshContent
 * @package Unbxd\ProductFeed\Controller\Adminhtml\LogViewer
 */
class RefreshContent extends LogViewer
{
    /**
     * @return \Magento\Framework\Controller\Result\Json
     * @throws \Exception
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $responseContent = [];

        $type = $this->getRequest()->getParam('type');
        // check if we know which log type need to be retrieved
        if (!$type) {
            $responseContent = [
                'errors' => true,
                'message' => __('Empty request data: log type is required.')
            ];
            $resultJson->setData($responseContent);
            return $resultJson;
        }

        /** @var \Unbxd\ProductFeed\Logger\File $logger */
        $logger = $this->getLogger($type);

        $responseContent = [
            'updatedContent' => nl2br($logger->getFileContent())
        ];

        $resultJson->setData($responseContent);
        return $resultJson;
    }
}