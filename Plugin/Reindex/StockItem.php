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
namespace Unbxd\ProductFeed\Plugin\Reindex;

use Magento\Framework\Indexer\IndexerRegistry;
use Unbxd\ProductFeed\Model\Indexer\Product as UnbxdProductIndexer;
use Unbxd\ProductFeed\Model\IndexingQueue\Handler;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Model\ResourceModel\Stock\Item as StockItemResourceModel;
use Unbxd\ProductFeed\Helper\ProductHelper;

/**
 * Class provides plugins to force reindex after stock item action processing
 *
 * Class StockItem
 * @package Unbxd\ProductFeed\Plugin\Reindex
 */
class StockItem
{
    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * @var ProductHelper
     */
    private $productHelper;

    /**
     * Indexer instance
     *
     * @var object
     */
    private $indexer = null;

    /**
     * StockItem constructor.
     * @param IndexerRegistry $indexerRegistry
     * @param ProductHelper $productHelper
     */
    public function __construct(
        IndexerRegistry $indexerRegistry,
        ProductHelper $productHelper
    ) {
        $this->indexerRegistry = $indexerRegistry;
        if (!$this->indexer) {
            $this->indexer = $indexerRegistry->get(UnbxdProductIndexer::INDEXER_ID);
        }
        $this->productHelper = $productHelper;
    }

    /**
     * @param StockItemResourceModel $stockItemResourceModel
     * @param \Closure $proceed
     * @param StockItemInterface $stockItem
     * @return mixed
     */
    public function aroundSave(
        StockItemResourceModel $stockItemResourceModel,
        \Closure $proceed,
        StockItemInterface $stockItem
    ) {
        $stockItemResourceModel->addCommitCallback(function () use ($stockItem) {
            if (
                !$this->indexer->isScheduled()
                && $this->productHelper->isProductTypeSupported($stockItem->getProduct()->getTypeId())
            ) {
                $id = $stockItem->getProductId();
                Handler::$additionalInformation[$id] = __('Product with ID %1 was updated', $id);
                // if indexer is 'Update on save' mode we need to rebuild related index data
                $this->indexer->reindexRow($id);
            }
        });

        return $proceed($stockItem);
    }

    /**
     * @param StockItemResourceModel $stockItemResourceModel
     * @param \Closure $proceed
     * @param StockItemInterface $stockItem
     * @return mixed
     */
    public function aroundDelete(
        StockItemResourceModel $stockItemResourceModel,
        \Closure $proceed,
        StockItemInterface $stockItem
    ) {
        $stockItemResourceModel->addCommitCallback(function () use ($stockItem) {
            if (
                !$this->indexer->isScheduled()
                && $this->productHelper->isProductTypeSupported($stockItem->getProduct()->getTypeId())
            ) {
                $id = $stockItem->getProductId();
                Handler::$additionalInformation[$id] = __('Product with ID %1 was deleted', $id);
                // if indexer is 'Update on save' mode we need to rebuild related index data
                $this->indexer->reindexRow($stockItem->getProductId());
            }
        });

        return $proceed($stockItem);
    }
}