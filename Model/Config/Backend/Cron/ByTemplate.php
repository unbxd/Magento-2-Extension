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
namespace Unbxd\ProductFeed\Model\Config\Backend\Cron;

use Magento\Cron\Model\Config\Source\Frequency;
use Unbxd\ProductFeed\Model\Config\Backend\Cron;
use Unbxd\ProductFeed\Model\Config\Source\CronType;

/**
 * Class ByTemplate
 * @package Unbxd\ProductFeed\Model\Config\Backend\Cron
 */
class ByTemplate extends Cron
{
    /**
     * Cron settings after save
     *
     * @return \Magento\Framework\App\Config\Value
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterSave()
    {
        if ($this->getIsCronIsEnabled() && ($this->getCronType() == CronType::TEMPLATE)) {
            $time = $this->getData(self::XML_PATH_CRON_TYPE_TEMPLATE_TIME);
            $frequency = $this->getData(self::XML_PATH_CRON_TYPE_TEMPLATE_FREQUENCY);

            $cronExprArray = [
                intval($time[1]),                                       # minute
                intval($time[0]),                                       # hour
                ($frequency == Frequency::CRON_MONTHLY) ? '1' : '*',    # day of the month
                '*',                                                    # month of the Year
                ($frequency == Frequency::CRON_WEEKLY) ? '1' : '*',     # day of the Week
            ];
            $cronExprString = join(' ', $cronExprArray);
            $this->updateConfigValues($cronExprString);
        }

        return parent::afterSave();
    }
}