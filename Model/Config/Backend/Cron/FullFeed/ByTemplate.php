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
namespace Unbxd\ProductFeed\Model\Config\Backend\Cron\FullFeed;

use Unbxd\ProductFeed\Model\Config\Backend\Cron\FullFeed;
use Unbxd\ProductFeed\Model\Config\Source\CronType;
use Magento\Cron\Model\Config\Source\Frequency;

/**
 * Class ByTemplate
 * @package Unbxd\ProductFeed\Model\Config\Backend\Cron\FullFeed
 */
class ByTemplate extends FullFeed
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
            $time = $this->getData(self::XML_PATH_CRON_FULL_TYPE_TEMPLATE_TIME);
            $frequency = $this->getData(self::XML_PATH_CRON_FULL_TYPE_TEMPLATE_FREQUENCY);

            $cronExprArray = [
                intval($time[1]),                                       # minute
                intval($time[0]),                                       # hour
                ($frequency == Frequency::CRON_MONTHLY) ? '1' : '*',    # day of the month
                '*',                                                    # month of the Year
                ($frequency == Frequency::CRON_WEEKLY) ? '1' : '*',     # day of the Week
            ];
            $cronExprString = join(' ', $cronExprArray);
            $this->updateConfigValues(
                self::CRON_FULL_STRING_PATH,
                self::CRON_FULL_MODEL_PATH,
                $cronExprString
            );
        }

        return parent::afterSave();
    }
}