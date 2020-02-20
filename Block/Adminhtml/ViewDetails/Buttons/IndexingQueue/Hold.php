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
 * Class Hold
 * @package Unbxd\ProductFeed\Block\Adminhtml\ViewDetails\Buttons\IndexingQueue
 */
class Hold extends Buttons implements ButtonProviderInterface
{
    /**
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label' => __('Hold'),
            'class' => 'primary',
            'on_click' => 'deleteConfirm(\'' . __('Put On Hold?') . '\', \'' . $this->getHoldUrl() . '\')',
            'sort_order' => 20
        ];
    }

    /**
     * Get URL for button action
     *
     * @return string
     */
    public function getHoldUrl()
    {
        return $this->getUrl(
            '*/*/hold',
            [
                'id' => $this->getQueueItemId()
            ]
        );
    }
}
