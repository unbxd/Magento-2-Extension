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
namespace Unbxd\ProductFeed\Controller\Adminhtml\Cron;

use Unbxd\ProductFeed\Controller\Adminhtml\ActionIndex;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class Check
 * @package Unbxd\ProductFeed\Controller\Adminhtml\Cron
 * @deprecated
 */
class Check extends ActionIndex
{
    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $responseContent = [];

        $isValid = $this->_isValidPostRequest();
        if (!$isValid) {
            $responseContent = [
                'errors' => true,
                'message' => __('Invalid request.')
            ];
            $resultJson->setData($responseContent);
            return $resultJson;
        }

        $limit = $this->getRequest()->getParam('limit');
        try {
            // try to fetch existing cron jobs over the last 24hrs
            $jobs = $this->cronManager->getCronJobs($limit);
            $responseContent = [
                'success' => true,
                'content' => $this->getCronJobsHtml($jobs)
            ];
        } catch (\Exception $e) {
            $responseContent = [
                'errors' => true,
                'message' => __($e->getMessage())
            ];
        }

        $resultJson->setData($responseContent);
        return $resultJson;
    }

    /**
     * Build response html
     *
     * @param $jobs
     * @return string
     */
    private function getCronJobsHtml($jobs)
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        $blockInstance = $resultPage->getLayout()
            ->createBlock('Magento\Framework\View\Element\Template')
            ->setTemplate('Unbxd_ProductFeed::cron-jobs.phtml')
            ->setCronJobs($jobs);

        return $blockInstance ? $blockInstance->toHtml() : '';
    }
}