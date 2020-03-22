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
namespace Unbxd\ProductFeed\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;

/**
 * Class AdditionalToolbar
 * @package Unbxd\ProductFeed\Block\Adminhtml
 */
class AdditionalToolbar extends Template
{
    /**#@+
     * Item keys
     */
    const ITEM_SETUP = 'setup';
    const ITEM_CATALOG = 'catalog';
    const ITEM_RELATED_CRON_JOBS = 'cron_jobs';
    const ITEM_INDEXING_QUEUE = 'indexing_queue';
    const ITEM_FEED_VIEW = 'feed_view';
    /**#@-*/

    /**
     * Current item key
     *
     * @var string|null
     */
    private $currentItemKey = null;

    /**
     * Path to template file.
     *
     * @var string
     */
    protected $_template = 'Unbxd_ProductFeed::additional-toolbar.phtml';

    /**
     * @var string
     */
    private $listingView;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->listingView = isset($data['listingView']) ? $data['listingView'] : null;
    }

    /**
     * Set listing view type
     *
     * @param $listingView
     * @return $this
     */
    public function setListingView($listingView)
    {
        if (!$this->listingView) {
            $this->listingView = $listingView;
        }

        return $this;
    }

    /**
     * Get listing view type
     *
     * @return mixed|string|null
     */
    public function getListingView()
    {
        return $this->listingView;
    }

    /**
     * Get current menu item
     *
     * @return string
     */
    public function getCurrentItemKey()
    {
        return $this->currentItemKey;
    }

    /**
     * Set current menu item
     *
     * @param string $key
     *
     * @return $this
     */
    public function setCurrentItemKey($key)
    {
        $this->currentItemKey = $key;

        return $this;
    }

    /**
     * Get current menu title
     *
     * @return string
     */
    public function getCurrentItemTitle()
    {
        $items = $this->getItems();
        $key = $this->getCurrentItemKey();
        if (!array_key_exists($key, $items)) {
            return '';
        }

        return isset($items[$key]['title']) ? trim($items[$key]['title']) : '';
    }

    /**
     * Get default menu items
     *
     * @return array
     */
    private function getDefaultItems()
    {
        return [
            self::ITEM_SETUP => [
                'title' => __('Setup Settings'),
                'url' => $this->getUrl('adminhtml/system_config/edit/section/unbxd_setup'),
                'target' => '',
                'class' => 'additional-toolbar-item default'
            ],
            self::ITEM_CATALOG => [
                'title' => __('Catalog Settings'),
                'url' => $this->getUrl('adminhtml/system_config/edit/section/unbxd_catalog'),
                'target' => '',
                'class' => 'additional-toolbar-item default'
            ],
            self::ITEM_INDEXING_QUEUE => [
                'title' => __('Indexing Queue'),
                'url' => $this->getUrl('unbxd_productfeed/indexing/queue'),
                'target' => '',
                'class' => 'additional-toolbar-item',
            ],
            self::ITEM_FEED_VIEW => [
                'title' => __('Feed View'),
                'url' => $this->getUrl('unbxd_productfeed/feed/view'),
                'target' => '',
                'class' => 'additional-toolbar-item',
            ],
            self::ITEM_RELATED_CRON_JOBS => [
                'title' => __('Related Cron Jobs'),
                'url' => $this->getUrl('unbxd_productfeed/cron/view'),
                'target' => '',
                'class' => 'additional-toolbar-item default'
            ]
        ];
    }

    /**
     * Prepared menu items. Removed current item for toolbar menu
     *
     * @return array
     */
    public function getItems()
    {
        $items = $this->getDefaultItems();
        if (($this->getListingView() == self::ITEM_FEED_VIEW) && isset($items[self::ITEM_FEED_VIEW])) {
            unset($items[self::ITEM_FEED_VIEW]);
        }
        if (($this->getListingView() == self::ITEM_INDEXING_QUEUE) && isset($items[self::ITEM_INDEXING_QUEUE])) {
            unset($items[self::ITEM_INDEXING_QUEUE]);
        }
        if (($this->getListingView() == self::ITEM_RELATED_CRON_JOBS) && isset($items[self::ITEM_RELATED_CRON_JOBS])) {
            unset($items[self::ITEM_RELATED_CRON_JOBS]);
        }

        return $items;
    }
}