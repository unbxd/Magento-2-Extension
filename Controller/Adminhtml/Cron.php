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
namespace Unbxd\ProductFeed\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Unbxd\ProductFeed\Model\ResourceModel\Cron\Collection;
use Unbxd\ProductFeed\Model\ResourceModel\Cron\CollectionFactory;
use Magento\Cron\Model\ResourceModel\Schedule as CronResource;

/**
 * Class Cron
 * @package Unbxd\ProductFeed\Controller\Adminhtml
 */
abstract class Cron extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Unbxd_ProductFeed::cron';

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var CronResource
     */
    private $cronResource;

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * MassDelete constructor.
     * @param Context $context
     * @param CollectionFactory $collectionFactory
     * @param CronResource $cronResource
     * @param Filter $filter
     */
    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        CronResource $cronResource,
        Filter $filter
    ) {
        parent::__construct($context);
        $this->collectionFactory = $collectionFactory;
        $this->cronResource = $cronResource;
        $this->filter = $filter;
    }

    /**
     * @param Collection $collection
     * @return $this
     */
    protected function processDelete(Collection $collection)
    {
        $deletedJobs = 0;
        try {
            foreach ($collection as $job) {
                /** @var \Magento\Cron\Model\Schedule $job */
                $this->cronResource->delete($job);
                $deletedJobs++;
            }
            $this->messageManager->addSuccessMessage(
                __('A total of %1 task(s) have been deleted.', $deletedJobs)
            );
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
        }

        return $this;
    }
}