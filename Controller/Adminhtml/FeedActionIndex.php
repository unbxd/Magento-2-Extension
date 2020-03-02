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
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Unbxd\ProductFeed\Helper\Feed as FeedHelper;
use Unbxd\ProductFeed\Helper\ProductHelper;
use Unbxd\ProductFeed\Model\FeedView;
use Unbxd\ProductFeed\Model\Indexer\Product\Full\Action\Full as ReindexAction;
use Unbxd\ProductFeed\Model\Feed\Manager as FeedManager;

/**
 * Class FeedActionIndex
 * @package Unbxd\ProductFeed\Controller\Adminhtml
 */
abstract class FeedActionIndex extends Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var FeedHelper
     */
    protected $feedHelper;

    /**
     * @var ProductHelper
     */
    protected $productHelper;

    /**
     * @var ReindexAction
     */
    protected $reindexAction;

    /**
     * @var FeedManager
     */
    protected $feedManager;

    /**
     * FeedActionIndex constructor.
     * @param Action\Context $context
     * @param PageFactory $resultPageFactory
     * @param StoreManagerInterface $storeManager
     * @param FeedHelper $feedHelper
     * @param ProductHelper $productHelper
     * @param ReindexAction $reindexAction
     * @param FeedManager $feedManager
     */
    public function __construct(
        Action\Context $context,
        PageFactory $resultPageFactory,
        StoreManagerInterface $storeManager,
        FeedHelper $feedHelper,
        ProductHelper $productHelper,
        ReindexAction $reindexAction,
        FeedManager $feedManager
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->storeManager = $storeManager;
        $this->feedHelper = $feedHelper;
        $this->productHelper = $productHelper;
        $this->reindexAction = $reindexAction;
        $this->feedManager = $feedManager;
    }

    /**
     * @return bool
     */
    protected function isValidPostRequest()
    {
        $formKeyIsValid = $this->_formKeyValidator->validate($this->getRequest());
        $isAjax = $this->getRequest()->isAjax();
        $isPost = $this->getRequest()->isPost();

        return (bool) ($formKeyIsValid && $isAjax && $isPost);
    }

    /**
     * @param string $store
     * @return \Magento\Store\Api\Data\StoreInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getStore($store = '')
    {
        return $this->storeManager->getStore($store);
    }

    /**
     * @param null $store
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getCurrentStoreId($store = null)
    {
        return $this->_request->getParam(Store::ENTITY, $this->getStore($store)->getId());
    }
}