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

use Unbxd\ProductFeed\Block\Adminhtml\ViewDetails\Tab\Products\IndexingQueue as IndexingQueueProductsTab;
use Unbxd\ProductFeed\Block\Adminhtml\ViewDetails\Tab\Products\FeedView as FeedViewProductsTab;
use Unbxd\ProductFeed\Model\IndexingQueue;


/**
 * Class ViewDetails
 * @package Unbxd\ProductFeed\Block\Adminhtml
 */
class ViewDetails extends \Magento\Backend\Block\Template
{
    /**
     * Block template
     *
     * @var string
     */
    protected $_template = 'Unbxd_ProductFeed::view-details/products.phtml';

    /**
     * @var \Unbxd\ProductFeed\Block\Adminhtml\Indexing\Queue\Item\Tab\Products
     */
    protected $productsGrid;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * ViewDetails constructor.
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
        parent::__construct($context, $data);
    }

    /**
     * Retrieve instance of grid block
     *
     * @return \Magento\Framework\View\Element\BlockInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getProductsGrid()
    {
        if (null === $this->productsGrid) {
            // default controller name (indexing queue)
            $instanceClassName = IndexingQueueProductsTab::class;
            if ($this->_request->getControllerName() == 'feed_view') {
                // controller name for feed view
                $instanceClassName = FeedViewProductsTab::class;
            }

            $this->productsGrid = $this->getLayout()->createBlock($instanceClassName, 'products.grid');
        }

        return $this->productsGrid;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getGridHtml()
    {
        return $this->getProductsGrid()->toHtml();
    }

    /**
     * @return string
     */
    public function getCatalogUrl()
    {
        return $this->getUrl('catalog/product/index');
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return IndexingQueue::REINDEX_FULL_LABEL;
    }
}
