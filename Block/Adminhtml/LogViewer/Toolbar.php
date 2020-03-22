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
namespace Unbxd\ProductFeed\Block\Adminhtml\LogViewer;

/**
 * Class Toolbar
 * @package Unbxd\ProductFeed\Block\Adminhtml\LogViewer
 */
abstract class Toolbar extends \Magento\Backend\Block\Widget\Container
{
    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return \Magento\Backend\Block\Widget\Container
     */
    protected function _prepareLayout()
    {
        $this->addButtonData();
        return parent::_prepareLayout();
    }

    /**
     * @return mixed
     */
    abstract protected function addButtonData();

    /**
     * @return string
     */
    public function getActionUrl($path)
    {
        return $this->getUrl($path);
    }
}