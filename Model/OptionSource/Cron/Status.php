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
namespace Unbxd\ProductFeed\Model\OptionSource\Cron;

use Magento\Framework\Option\ArrayInterface;
use Magento\Cron\Model\Schedule;

/**
 * Class Status
 * @package Unbxd\ProductFeed\Model\OptionSource\Cron
 */
class Status implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => Schedule::STATUS_PENDING,
                'label' => '<span class="grid-severity-minor">' . __('Pending') . '</span>'
            ],
            [
                'value' => Schedule::STATUS_RUNNING,
                'label' => '<span class="grid-severity-minor">' . __('Running'). '</span>'
            ],
            [
                'value' => Schedule::STATUS_SUCCESS,
                'label' => '<span class="grid-severity-notice">' . __('Success') . '</span>'
            ],
            [
                'value' => Schedule::STATUS_ERROR,
                'label' => '<span class="grid-severity-critical">' . __('Error') . '</span>'
            ],
            [
                'value' => Schedule::STATUS_MISSED,
                'label' => '<span class="grid-severity-critical">' . __('Missed') . '</span>'
            ]
        ];
    }
}