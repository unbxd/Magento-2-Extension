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
namespace Unbxd\ProductFeed\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;
use Unbxd\ProductFeed\Model\CronManager;
use Unbxd\ProductFeed\Helper\Data as HelperData;

/**
 * Class ViewIndex
 * @package Unbxd\ProductFeed\Controller\Adminhtml
 */
abstract class ViewIndex extends Action
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * ViewIndex constructor.
     * @param Action\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param PageFactory $resultPageFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Action\Context $context,
        \Magento\Framework\Registry $registry,
        PageFactory $resultPageFactory,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->registry = $registry;
        $this->resultPageFactory = $resultPageFactory;
        $this->storeManager = $storeManager;
    }
}