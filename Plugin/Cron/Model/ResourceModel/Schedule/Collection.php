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
namespace Unbxd\ProductFeed\Plugin\Cron\Model\ResourceModel\Schedule;

use Magento\Cron\Model\ResourceModel\Schedule\Collection as ScheduleCollection;

/**
 * Class Collection
 * @package Unbxd\ProductFeed\Plugin\Cron\Model\ResourceModel\Schedule
 */
class Collection
{
    /**#@+
     * Schedule ID field
     */
    const ID_FIELD = 'schedule_id';
    /**#@-*/

    /**
     * @param ScheduleCollection $subject
     * @param $result
     * @return string
     */
    public function afterGetIdFieldName(
        ScheduleCollection $subject,
        $result
    ) {
        if ($result === null) {
            $result = self::ID_FIELD;
        }

        return $result;
    }
}
