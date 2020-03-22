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
namespace Unbxd\ProductFeed\Model\ResourceModel\Cron;

use Magento\Cron\Model\ResourceModel\Schedule\Collection as ScheduleCollection;
use Unbxd\ProductFeed\Model\CronManager;
use Magento\Framework\App\ObjectManager;

/**
 * Class Collection
 * @package Unbxd\ProductFeed\Model\ResourceModel\Cron
 */
class Collection extends ScheduleCollection
{
    /**
     * @var null|CronManager
     */
    private $cronManager = null;

    /**
     * @return ScheduleCollection
     */
    public function filterCollectionByRelatedJobs()
    {
        $collection = $this->getCronManager()->filterCollectionByJobCode(
            $this,
            CronManager::FEED_JOB_CODE_PREFIX
        );

        return $collection;
    }

    /**
     * @return mixed|CronManager|null
     */
    private function getCronManager()
    {
        if (null === $this->cronManager) {
            $this->cronManager = ObjectManager::getInstance()->get(
                \Unbxd\ProductFeed\Model\CronManagerFactory::class
            )->create();
        }

        return $this->cronManager;
    }
}