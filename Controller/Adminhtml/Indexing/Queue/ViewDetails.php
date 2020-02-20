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
namespace Unbxd\ProductFeed\Controller\Adminhtml\Indexing\Queue;

use Magento\Backend\App\Action;
use Unbxd\ProductFeed\Controller\Adminhtml\ViewIndex;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Controller\ResultFactory;
use Unbxd\ProductFeed\Model\IndexingQueue;
use Unbxd\ProductFeed\Model\IndexingQueueFactory;
use Unbxd\ProductFeed\Model\ResourceModel\IndexingQueue as IndexingQueueResourceModel;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class ViewDetails
 * @package Unbxd\ProductFeed\Controller\Adminhtml\Indexing\Queue
 */
class ViewDetails extends ViewIndex
{
    /**
     * @var IndexingQueueResourceModel
     */
    protected $indexingQueueResource;

    /**
     * @var IndexingQueue
     */
    protected $indexingQueueFactory;

    /**
     * ViewDetails constructor.
     * @param Action\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param PageFactory $resultPageFactory
     * @param StoreManagerInterface $storeManager
     * @param IndexingQueueFactory $indexingQueueFactory
     * @param IndexingQueueResourceModel $indexingQueueResource
     */
    public function __construct(
        Action\Context $context,
        \Magento\Framework\Registry $registry,
        PageFactory $resultPageFactory,
        StoreManagerInterface $storeManager,
        IndexingQueueFactory $indexingQueueFactory,
        IndexingQueueResourceModel $indexingQueueResource
    ) {
        parent::__construct(
            $context,
            $registry,
            $resultPageFactory,
            $storeManager
        );
        $this->indexingQueueResource = $indexingQueueResource;
        $this->indexingQueueFactory = $indexingQueueFactory
            ?: \Magento\Framework\App\ObjectManager::getInstance()->get(IndexingQueueFactory::class);
    }

    /**
     * @return bool|\Magento\Backend\Model\View\Result\Redirect
     * @throws LocalizedException
     */
    private function initAction()
    {
        $id = $this->getRequest()->getParam('id');
        /** @var \Unbxd\ProductFeed\Model\IndexingQueue $model */
        $model = $this->indexingQueueFactory->create();
        // check if we know for what should be rendered layout
        if ($id) {
            // try to load model via resource model instead of use AbstractModel::load()
            // as he deprecated since 100.1.0 @see \Magento\Framework\Model\AbstractModel::load()
            $this->indexingQueueResource->load($model, $id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This queue item no longer exists.'));
                /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }

        $this->registry->register('indexing_queue_item', $model);

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
        $resultPage->addBreadcrumb(__('Queue Item Details'), __('Queue Item Details'));
        $resultPage->getConfig()->getTitle()->prepend(__('Unbxd | Queue Item Details'));

        return $resultPage;
    }
}