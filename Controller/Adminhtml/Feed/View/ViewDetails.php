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
namespace Unbxd\ProductFeed\Controller\Adminhtml\Feed\View;

use Magento\Backend\App\Action;
use Unbxd\ProductFeed\Controller\Adminhtml\ViewIndex;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Controller\ResultFactory;
use Unbxd\ProductFeed\Model\FeedView;
use Unbxd\ProductFeed\Model\FeedViewFactory;
use Unbxd\ProductFeed\Model\ResourceModel\FeedView as FeedViewResourceModel;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class ViewDetails
 * @package Unbxd\ProductFeed\Controller\Adminhtml\Indexing\Queue
 */
class ViewDetails extends ViewIndex
{
    /**
     * @var FeedViewResourceModel
     */
    protected $feedViewResource;

    /**
     * @var FeedView
     */
    protected $feedViewFactory;

    /**
     * ViewDetails constructor.
     * @param Action\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param PageFactory $resultPageFactory
     * @param StoreManagerInterface $storeManager
     * @param FeedViewFactory $feedViewFactory
     * @param FeedViewResourceModel $feedViewResource
     */
    public function __construct(
        Action\Context $context,
        \Magento\Framework\Registry $registry,
        PageFactory $resultPageFactory,
        StoreManagerInterface $storeManager,
        FeedViewFactory $feedViewFactory,
        FeedViewResourceModel $feedViewResource
    ) {
        parent::__construct(
            $context,
            $registry,
            $resultPageFactory,
            $storeManager
        );
        $this->feedViewFactory = $feedViewFactory
            ?: \Magento\Framework\App\ObjectManager::getInstance()->get(FeedViewFactory::class);
        $this->feedViewResource = $feedViewResource;
    }

    /**
     * @return bool|\Magento\Backend\Model\View\Result\Redirect
     * @throws LocalizedException
     */
    private function initAction()
    {
        $id = $this->getRequest()->getParam('id');
        /** @var \Unbxd\ProductFeed\Model\FeedView $model */
        $model = $this->feedViewFactory->create();
        // check if we know for what should be rendered layout
        if ($id) {
            // try to load model via resource model instead of use AbstractModel::load()
            // as it's deprecated since 100.1.0 @see \Magento\Framework\Model\AbstractModel::load()
            $this->feedViewResource->load($model, $id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This feed view no longer exists.'));
                /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }

        $this->registry->register('feed_view_item', $model);

        return true;
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Page|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws LocalizedException
     */
    public function execute()
    {
        $this->initAction();

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Unbxd_ProductFeed::productfeed');
        $resultPage->addBreadcrumb(__('Feed View Details'), __('Feed View Details'));
        $resultPage->getConfig()->getTitle()->prepend(__('Unbxd | Feed View Details'));

        return $resultPage;
    }
}