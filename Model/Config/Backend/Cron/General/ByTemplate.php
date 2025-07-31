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

namespace Unbxd\ProductFeed\Model\Config\Backend\Cron\General;

use Unbxd\ProductFeed\Model\Config\Backend\Cron\General;
use Unbxd\ProductFeed\Model\Config\Source\CronType;
use Magento\Cron\Model\Config\Source\Frequency;

/**
 * Class ByTemplate
 * @package Unbxd\ProductFeed\Model\Config\Backend\Cron\General
 */
class ByTemplate extends General
{
    /**
     * Cron settings after save
     *
     * @return \Magento\Framework\App\Config\Value
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterSave()
    {
        $time = $this->getData(self::XML_PATH_CRON_GENERAL_TYPE_TEMPLATE_TIME);
        $frequency = $this->getData(self::XML_PATH_CRON_GENERAL_TYPE_TEMPLATE_FREQUENCY);

        $cronExprArray = [
            intval($time[1]),                                       # minute
            intval($time[0]),                                       # hour
            ($frequency == Frequency::CRON_MONTHLY) ? '1' : '*',    # day of the month
            '*',                                                    # month of the Year
            ($frequency == Frequency::CRON_WEEKLY) ? '1' : '*',     # day of the Week
        ];
        $cronExprString = join(' ', $cronExprArray);
        $this->updateConfigValues(
            self::CRON_GENERAL_STRING_PATH,
            self::CRON_GENERAL_MODEL_PATH,
            $cronExprString
        );

        return parent::afterSave();
    }
}
