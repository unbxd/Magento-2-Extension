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
use Magento\Catalog\Model\Product\Action;
use Unbxd\ProductFeed\Helper\ProductHelper;

/**
 * Class provides plugins to force reindex after product mass action processing
 *
 * Class ProductMassAction
 * @package Unbxd\ProductFeed\Plugin\Reindex
 */
class ProductMassAction
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
     * ProductMassAction constructor.
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
     * @param Action $subject
     * @param \Closure $closure
     * @param array $productIds
     * @param array $attrData
     * @param $storeId
     * @return mixed
     */
    public function aroundUpdateAttributes(
        Action $subject,
        \Closure $closure,
        array $productIds,
        array $attrData,
        $storeId
    ) {
        $result = $closure($productIds, $attrData, $storeId);
        if (!$this->indexer->isScheduled()) {
            $productIds = array_unique($productIds);
            $validProductIds = [];
            foreach ($productIds as $id) {
                /** @var \Magento\Catalog\Model\Product $product */
                $product = $this->productHelper->getProduct($id);
                if ($product && $this->productHelper->isProductTypeSupported($product->getTypeId())) {
                    Handler::$additionalInformation[$id] = __('Product with ID %1 was updated', $id);
                    $validProductIds[] = $id;
                }
            }
            if (!empty($validProductIds)) {
                // if indexer is 'Update on save' mode we need to rebuild related index data
                $this->indexer->reindexList($validProductIds);
            }
        }

        return $result;
    }

    /**
     * @param Action $subject
     * @param \Closure $closure
     * @param array $productIds
     * @param array $websiteIds
     * @param $type
     * @return mixed
     */
    public function aroundUpdateWebsites(
        Action $subject,
        \Closure $closure,
        array $productIds,
        array $websiteIds,
        $type
    ) {
        $result = $closure($productIds, $websiteIds, $type);
        if (!$this->indexer->isScheduled()) {
            $productIds = array_unique($productIds);
            $validProductIds = [];
            foreach ($productIds as $id) {
                /** @var \Magento\Catalog\Model\Product $product */
                $product = $this->productHelper->getProduct($id);
                if ($product && $this->productHelper->isProductTypeSupported($product->getTypeId())) {
                    Handler::$additionalInformation[$id] = __('Product with ID %1 was updated', $id);
                    $validProductIds[] = $id;
                }
            }
            if (!empty($validProductIds)) {
                // if indexer is 'Update on save' mode we need to rebuild related index data
                $this->indexer->reindexList($validProductIds);
            }
        }

        return $result;
    }
}