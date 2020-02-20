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
namespace Unbxd\ProductFeed\Block\Adminhtml\ViewDetails;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class Buttons
 * @package Unbxd\ProductFeed\Block\Adminhtml\ViewDetails
 */
class Buttons
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * Local cache for queue item id
     *
     * @var int|null
     */
    private $queueId = null;

    /**
     * Local cache for feed view id
     *
     * @var int|null
     */
    private $feedViewId = null;

    /**
     * Generic constructor.
     * @param Context $context
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Registry $registry
    ) {
        $this->context = $context;
        $this->registry = $registry;
    }

    /**
     * @return int|string|null
     */
    public function getQueueItemId()
    {
        if (!$this->queueId) {
            $model = $this->registry->registry('indexing_queue_item');
            if ($id = $model->getId()) {
                $this->queueId = $id;
            }
        }

        return $this->queueId;
    }

    /**
     * @return int|string|null
     */
    public function getFeedViewId()
    {
        if (!$this->feedViewId) {
            $model = $this->registry->registry('feed_view_item');
            if ($id = $model->getId()) {
                $this->feedViewId = $id;
            }
        }

        return $this->feedViewId;
    }

    /**
     * Generate url by route and parameters
     *
     * @param   string $route
     * @param   array $params
     * @return  string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}