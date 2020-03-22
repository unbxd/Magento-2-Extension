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
namespace Unbxd\ProductFeed\Block\Adminhtml\LogViewer\Toolbar;

use Unbxd\ProductFeed\Block\Adminhtml\LogViewer\Toolbar;

/**
 * Class FeedView
 * @package Unbxd\ProductFeed\Block\Adminhtml\LogViewer\Toolbar
 */
class FeedView extends Toolbar
{
    /**
     * @return mixed|void
     */
    protected function addButtonData()
    {
        $message = __('Are you sure do you want to clear feed view?');
        $this->buttonList->add(
            'clear',
            [
                'label' => __('Clear Feed View'),
                'class' => 'primary',
                'onclick' => "confirmSetLocation('{$message}', '{$this->getActionUrl('*/feed_view/deleteAll')}')",
            ]
        );
    }
}