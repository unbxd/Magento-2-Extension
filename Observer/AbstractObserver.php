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
namespace Unbxd\ProductFeed\Observer;

use Unbxd\ProductFeed\Helper\Data as HelperData;
use Magento\Framework\Message\ManagerInterface;

/**
 * Class AbstractObserver
 * @package Unbxd\ProductFeed\Observer
 */
abstract class AbstractObserver
{
    /**
     * System configuration Unbxd section param names
     *
     * setup
     */
    const SYSTEM_CONFIG_SECTION_PARAM_UNBXD_SETUP = 'unbxd_setup';

    /**
     * catalog
     */
    const SYSTEM_CONFIG_SECTION_PARAM_UNBXD_CATALOG = 'unbxd_catalog';

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * AbstractObserver constructor.
     * @param HelperData $helperData
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        HelperData $helperData,
        ManagerInterface $messageManager
    ) {
        $this->helperData = $helperData;
        $this->messageManager = $messageManager;
    }

    /**
     * @return mixed
     */
    protected function getAuthorizationCredentialsAreNotSetupMessage()
    {
        return __('Authorization credentials are not setup. Please provide them to perform operations 
            with Unbxd service.');
    }

    /**
     * @return mixed
     */
    protected function getIndexingQueueIsDisabledMessage()
    {
        return __('Indexing queue is disabled. Enabling this option is recommended in production mode 
            or with a large product catalog.');
    }

    /**
     * @return mixed
     */
    protected function getCronIsNotConfiguredMessage()
    {
        return __('Cron is not configured. Please configure it to perform asynchronous operations with Unbxd service.');
    }
}