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
namespace Unbxd\ProductFeed\Cron;

use Unbxd\ProductFeed\Model\CronManager;
use Unbxd\ProductFeed\Model\CronManagerFactory;

/**
 * Class CheckUploadedFeedStatus
 * @package Unbxd\ProductFeed\Cron
 */
class CheckUploadedFeedStatus
{
    /**
     * @var CronManagerFactory
     */
    protected $cronManagerFactory;

    /**
     * CheckUploadedFeedStatus constructor.
     * @param CronManagerFactory $cronManagerFactory
     */
    public function __construct(
        CronManagerFactory $cronManagerFactory
    ) {
        $this->cronManagerFactory = $cronManagerFactory;
    }

    /**
     * Check status for uploaded feed
     *
     * @throws \Exception
     */
    public function execute()
    {
        /** @var CronManager $cronManager */
        $cronManager = $this->cronManagerFactory->create();
        $cronManager->checkUploadedFeedStatus();
    }
}