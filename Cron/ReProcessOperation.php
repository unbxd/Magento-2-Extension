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
 * Class ReProcessOperation
 * @package Unbxd\ProductFeed\Cron
 */
class ReProcessOperation
{
    /**
     * @var CronManagerFactory
     */
    protected $cronManagerFactory;

    /**
     * ReProcessOperation constructor.
     * @param CronManagerFactory $cronManagerFactory
     */
    public function __construct(
        CronManagerFactory $cronManagerFactory
    ) {
        $this->cronManagerFactory = $cronManagerFactory;
    }

    /**
     * Re-process for operation(s) in 'ERROR' state
     *
     * @throws \Exception
     */
    public function execute()
    {
        /** @var CronManager $cronManager */
        $cronManager = $this->cronManagerFactory->create();
        $cronManager->reProcessOperation();
    }
}