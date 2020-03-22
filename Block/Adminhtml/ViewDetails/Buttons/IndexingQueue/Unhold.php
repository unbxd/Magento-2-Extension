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
namespace Unbxd\ProductFeed\Block\Adminhtml\ViewDetails\Buttons\IndexingQueue;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Unbxd\ProductFeed\Block\Adminhtml\ViewDetails\Buttons;

/**
 * Class Unhold
 * @package Unbxd\ProductFeed\Block\Adminhtml\ViewDetails\Buttons\IndexingQueue
 */
class Unhold extends Buttons implements ButtonProviderInterface
{
    /**
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label' => __('Unhold'),
            'class' => 'primary',
            'on_click' => 'deleteConfirm(\'' . __('Unhold?') . '\', \'' . $this->getUnholdUrl() . '\')',
            'sort_order' => 30
        ];
    }

    /**
     * Get URL for button action
     *
     * @return string
     */
    public function getUnholdUrl()
    {
        return $this->getUrl(
            '*/*/unhold',
            [
                'id' => $this->getQueueItemId()
            ]
        );
    }
}
