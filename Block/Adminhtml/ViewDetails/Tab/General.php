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
namespace Unbxd\ProductFeed\Block\Adminhtml\ViewDetails\Tab;

/**
 * Class General
 * @package Unbxd\ProductFeed\Block\Adminhtml\Indexing\Queue\Item\Tab
 */
abstract class General extends \Magento\Backend\Block\Template
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * General constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->registry = $registry;
        parent::__construct(
            $context,
            $data
        );
    }

    /**
     * @param $date
     * @return string
     */
    public function getFormattedDate($date)
    {
        return $this->formatDate(
            $date,
            \IntlDateFormatter::MEDIUM,
            true
        );
    }

    /**
     * Retrieve current item instance
     *
     * @return mixed
     */
    abstract public function getItem();
}