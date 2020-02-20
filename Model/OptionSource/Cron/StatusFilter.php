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
class StatusFilter implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => Schedule::STATUS_PENDING,
                'label' => __('Pending')
            ],
            [
                'value' => Schedule::STATUS_RUNNING,
                'label' => __('Running')
            ],
            [
                'value' => Schedule::STATUS_SUCCESS,
                'label' => __('Success')
            ],
            [
                'value' => Schedule::STATUS_ERROR,
                'label' => __('Error')
            ],
            [
                'value' => Schedule::STATUS_MISSED,
                'label' => __('Missed')
            ]
        ];
    }
}