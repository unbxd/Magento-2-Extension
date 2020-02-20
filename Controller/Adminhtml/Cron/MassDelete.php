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

use Unbxd\ProductFeed\Controller\Adminhtml\Cron;
use Magento\Framework\Controller\ResultFactory;
use Unbxd\ProductFeed\Model\ResourceModel\Cron\Collection;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class MassDelete
 * @package Unbxd\ProductFeed\Controller\Adminhtml\Cron
 */
class MassDelete extends Cron
{
    /**
     * @return \Magento\Backend\Model\View\Result\Redirect|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws LocalizedException
     */
    public function execute()
    {
        $this->filter->applySelectionOnTargetProvider();

        /** @var Collection $collection */
        $collection = $this->filter->getCollection($this->collectionFactory->create());

        if ($collection->getSize()) {
            $this->processDelete($collection);
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setRefererUrl();
    }
}