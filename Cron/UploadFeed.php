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
 * Class UploadFeed
 * @package Unbxd\ProductFeed\Cron
 */
class UploadFeed
{
    /**
     * @var CronManagerFactory
     */
    protected $cronManagerFactory;

    /**
     * UploadFeed constructor.
     * @param CronManagerFactory $cronManagerFactory
     */
    public function __construct(
        CronManagerFactory $cronManagerFactory
    ) {
        $this->cronManagerFactory = $cronManagerFactory;
    }

    /**
     * Run indexing/feed operation(s) by schedule
     *
     * @throws \Exception
     */
    public function execute()
    {
        /** @var CronManager $cronManager */
        $cronManager = $this->cronManagerFactory->create();
        $cronManager->uploadFeed();
    }
}