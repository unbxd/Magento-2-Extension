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

use Unbxd\ProductFeed\Model\Config\Backend\Cron;
use Unbxd\ProductFeed\Model\Config\Source\CronType;

/**
 * Class Manually
 * @package Unbxd\ProductFeed\Model\Config\Backend\Cron
 */
class Manually extends Cron
{
    /**
     * Cron settings after save
     *
     * @return \Magento\Framework\App\Config\Value
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterSave()
    {
        if ($this->getIsCronIsEnabled() && ($this->getCronType() == CronType::MANUALLY)) {
            $this->updateConfigValues($this->getValue());
        }

        return parent::afterSave();
    }
}