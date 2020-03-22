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
 * Class Back
 * @package Unbxd\ProductFeed\Block\Adminhtml\ViewDetails\Buttons\IndexingQueue
 */
class Back extends Buttons implements ButtonProviderInterface
{
    /**
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label' => __('Back'),
            'on_click' => sprintf("location.href = '%s';", $this->getBackUrl()),
            'class' => 'back',
            'sort_order' => 10
        ];
    }

    /**
     * Get URL for button action
     *
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('*/indexing/queue');
    }
}
