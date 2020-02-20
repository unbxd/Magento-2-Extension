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

use Magento\Backend\Block\Widget\Grid;
use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Backend\Block\Widget\Grid\Extended as GridExtended;
use Unbxd\ProductFeed\Block\Adminhtml\Indexing\Queue\Item\Tab\Products\Grid\Column\Renderer\StockStatus;
use Unbxd\ProductFeed\Model\IndexingQueue;
use Unbxd\ProductFeed\Model\FeedView;
use Magento\CatalogInventory\Model\ResourceModel\Stock\StatusFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as CollectionFactory;

/**
 * Class Products
 * @package Unbxd\ProductFeed\Block\Adminhtml\ViewDetails\Tab
 */
abstract class Products extends GridExtended
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry = null;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var StatusFactory
     */
    private $stockStatusFactory;

    /**
     * Ids of current stores
     *
     * @var array
     */
    protected $storeIds = [];

    /**
     * Stores current currency code
     *
     * @var string
     */
    protected $currentCurrencyCode = null;

    /**
     * Products constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Framework\Registry $coreRegistry
     * @param CollectionFactory $collectionFactory
     * @param StockStatus $stockStatusFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Framework\Registry $coreRegistry,
        CollectionFactory $collectionFactory,
        StatusFactory $stockStatusFactory,
        array $data = []
    ) {
        $this->registry = $coreRegistry;
        $this->collectionFactory = $collectionFactory;
        $this->stockStatusFactory = $stockStatusFactory;
        parent::__construct(
            $context,
            $backendHelper,
            $data
        );
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('products_grid');
        $this->setDefaultSort('entity_id');
        $this->setUseAjax(true);
    }

    /**
     * @param Column $column
     * @return $this
     */
    protected function _addColumnFilterToCollection($column)
    {
        parent::_addColumnFilterToCollection($column);
        return $this;
    }

    /**
     * @return GridExtended
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareCollection()
    {
        /** @var IndexingQueue|FeedView $item */
        $item = $this->getItem();

        if ($this->isFullCatalog()) {
            // if full reindex action we don't want to display all catalog products (if a large catalog it will be not UI),
            // only related message will be displayed @see \Unbxd\ProductFeed\Model\IndexingQueue::REINDEX_FULL_LABEL
            return parent::_prepareCollection();
        }

        $entityIds = array_map(function($item) {
            return trim($item, '#');
        }, explode(', ', $item->getAffectedEntities()));

        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection */
        $collection = $this->collectionFactory->create();
        $collection->addAttributeToSelect(['name']);
        if (!empty($entityIds)) {
            $collection->addIdFilter($entityIds);
        }

        $this->addStockInformationToCollection($collection);

        $storeId = (int) $this->getRequest()->getParam('store', 0);
        if ($storeId > 0) {
            $collection->addStoreFilter($storeId);
        }

        $collection->setOrder('entity_id', \Magento\Framework\Data\Collection::SORT_ORDER_ASC);
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * Added stock information to product collection (currently we added qty and stock status)
     *
     * @param $collection
     */
    private function addStockInformationToCollection($collection)
    {
        $collection->joinField(
            'qty',
            $collection->getConnection()->getTableName('cataloginventory_stock_item'),
            'qty',
            'product_id=entity_id'
        );

        // format qty field: float -> integer
        $this->addExpressionFieldToSelect(
            $collection,
            'qty',
            new \Zend_Db_Expr(
                "(CASE WHEN at_qty.qty LIKE '%.0%' THEN FORMAT(at_qty.qty, 0) ELSE at_qty.qty END)"
            ),
            ['qty' => 'qty']
        );

        $stockFlag = 'has_stock_status_filter';
        if (!$collection->hasFlag($stockFlag)) {
            /** @var \Magento\CatalogInventory\Model\ResourceModel\Stock\Status $stockStatusResource */
            $stockStatusResource = $this->stockStatusFactory->create();
            $stockStatusResource->addStockDataToCollection($collection, false);
            $collection->setFlag($stockFlag, true);
        }
    }

    /**
     * Add attribute expression (SUM, COUNT, etc)
     * Example: ('sub_total', 'SUM({{attribute}})', 'revenue')
     * Example: ('sub_total', 'SUM({{revenue}})', 'revenue')
     * For some functions like SUM use groupByAttribute.
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection
     * @param string $alias
     * @param string $expression
     * @param array|string $fields
     * @return \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
     */
    public function addExpressionFieldToSelect($collection, $alias, $expression, $fields)
    {
        // validate alias
        if (!is_array($fields)) {
            $fields = [$fields => $fields];
        }

        $fullExpression = $expression;
        foreach ($fields as $fieldKey => $fieldItem) {
            $fullExpression = str_replace('{{' . $fieldKey . '}}', $fieldItem, $fullExpression);
        }

        $collection->getSelect()->columns([$alias => $fullExpression]);

        return $collection;
    }

    /**
     * @return GridExtended
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'entity_id',
            [
                'header' => __('ID'),
                'sortable' => true,
                'index' => 'entity_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );
        $this->addColumn(
            'thumbnail',
            [
                'header' => __('Thumbnail'),
                'sortable' => false,
                'index' => 'entity_id',
                'header_css_class' => 'col-image',
                'column_css_class' => 'col-image',
                'renderer' => 'Unbxd\ProductFeed\Block\Adminhtml\ViewDetails\Tab\Products\Grid\Column\Renderer\Image',
            ]
        );
        $this->addColumn(
            'name',
            [
                'header' => __('Name'),
                'index' => 'name'
            ]
        );
        $this->addColumn(
            'sku',
            [
                'header' => __('Sku'),
                'index' => 'sku'
            ]
        );
        $this->addColumn(
            'type_id',
            [
                'header' => __('Type'),
                'index' => 'type_id'
            ]
        );

        if ($this->getRequest()->getParam('website')) {
            $storeIds = $this->_storeManager->getWebsite($this->getRequest()->getParam('website'))->getStoreIds();
        } elseif ($this->getRequest()->getParam('group')) {
            $storeIds = $this->_storeManager->getGroup($this->getRequest()->getParam('group'))->getStoreIds();
        } elseif ($this->getRequest()->getParam('store')) {
            $storeIds = [(int)$this->getRequest()->getParam('store')];
        } else {
            $storeIds = [];
        }
        $this->setStoreIds($storeIds);
        $currencyCode = $this->getCurrentCurrencyCode();

        $this->addColumn(
            'price',
            [
                'header' => __('Price'),
                'type' => 'currency',
                'currency_code' => (string) $this->_scopeConfig->getValue(
                    \Magento\Directory\Model\Currency::XML_PATH_CURRENCY_BASE,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                ),
                'index' => 'entity_id',
                'renderer' => 'Unbxd\ProductFeed\Block\Adminhtml\ViewDetails\Tab\Products\Grid\Column\Renderer\Price',
                'rate' => $this->getRate($currencyCode),
            ]
        );
        $this->addColumn(
            'qty',
            [
                'header' => __('Qty'),
                'index' => 'qty'
            ]
        );
        $this->addColumn(
            'is_salable',
            [
                'header' => __('In Salable'),
                'index' => 'is_salable',
                'renderer' => 'Unbxd\ProductFeed\Block\Adminhtml\ViewDetails\Tab\Products\Grid\Column\Renderer\StockStatus'
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * @param $storeIds
     * @return $this
     */
    public function setStoreIds($storeIds)
    {
        $this->storeIds = $storeIds;
        return $this;
    }

    /**
     * Retrieve currency code based on selected store
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getCurrentCurrencyCode()
    {
        if ($this->currentCurrencyCode === null) {
            reset($this->storeIds);
            $this->currentCurrencyCode = count(
                $this->storeIds
            ) > 0 ? $this->_storeManager->getStore(
                current($this->storeIds)
            )->getBaseCurrencyCode() : $this->_storeManager->getStore()->getBaseCurrencyCode();
        }

        return $this->currentCurrencyCode;
    }

    /**
     * Get currency rate (base to given currency)
     *
     * @param $toCurrency
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getRate($toCurrency)
    {
        return $this->_storeManager->getStore()->getBaseCurrency()->getRate($toCurrency);
    }

    /**
     * @param \Magento\Framework\DataObject $row
     *
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('catalog/product/edit', ['id' => $row->getId()]);
    }

    /**
     * Retrieve current item instance
     *
     * @return mixed
     */
    abstract public function getItem();

    /**
     * Prepare data for collection
     *
     * @return mixed
     */
    abstract public function isFullCatalog();
}