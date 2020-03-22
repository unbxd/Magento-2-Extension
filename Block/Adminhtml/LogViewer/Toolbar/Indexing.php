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
 * Class Indexing
 * @package Unbxd\ProductFeed\Block\Adminhtml\LogViewer\Toolbar
 */
class Indexing extends Toolbar
{
    /**
     * @return mixed|void
     */
    protected function addButtonData()
    {
        $message = __('Are you sure do you want to clear indexing queue?');
        $this->buttonList->add(
            'clear',
            [
                'label' => __('Clear Queue'),
                'class' => 'primary',
                'onclick' => "confirmSetLocation('{$message}', '{$this->getActionUrl('*/indexing_queue/deleteAll')}')",
            ]
        );
    }
}