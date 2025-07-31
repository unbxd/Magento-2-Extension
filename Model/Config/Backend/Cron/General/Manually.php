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

/**
 * Class Manually
 * @package Unbxd\ProductFeed\Model\Config\Backend\Cron\General
 */
class Manually extends General
{
    /**
     * Cron settings after save
     *
     * @return \Magento\Framework\App\Config\Value
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterSave()
    {
            $this->updateConfigValues(
                self::CRON_GENERAL_STRING_PATH,
                self::CRON_GENERAL_MODEL_PATH,
                $this->getValue()
            );

        return parent::afterSave();
    }
}