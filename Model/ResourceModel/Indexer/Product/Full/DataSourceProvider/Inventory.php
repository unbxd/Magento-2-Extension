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
namespace Unbxd\ProductFeed\Model\ResourceModel\Indexer\Product\Full\DataSourceProvider;

use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Store\Model\StoreManagerInterface;
use Unbxd\ProductFeed\Model\ResourceModel\Eav\Indexer\Indexer;
use Magento\Framework\Indexer\Table\StrategyInterface;

/**
 * Product inventory data source resource model
 *
 * Class Inventory
 * @package Unbxd\ProductFeed\Model\ResourceModel\Indexer\Product\Full\DataSourceProvider
 */
class Inventory extends Indexer
{
    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @var StockConfigurationInterface
     */
    private $stockConfiguration;

    /**
     * @var int[]
     */
    private $stockIdByWebsite = [];

    /**
     * Inventory constructor.
     * @param ResourceConnection $resource
     * @param StrategyInterface $tableStrategy
     * @param StoreManagerInterface $storeManager
     * @param MetadataPool $metadataPool
     * @param StockRegistryInterface $stockRegistry
     * @param StockConfigurationInterface $stockConfiguration
     */
    public function __construct(
        ResourceConnection $resource,
        StrategyInterface $tableStrategy,
        StoreManagerInterface $storeManager,
        MetadataPool $metadataPool,
        StockRegistryInterface $stockRegistry,
        StockConfigurationInterface $stockConfiguration
    ) {
        $this->stockRegistry = $stockRegistry;
        $this->stockConfiguration = $stockConfiguration;
        parent::__construct(
            $resource,
            $tableStrategy,
            $storeManager,
            $metadataPool
        );
    }

    /**
     * Load inventory data for a list of product ids and a given store.
     *
     * @param $storeId
     * @param $productIds
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function loadInventoryData($storeId, $productIds)
    {
        $websiteId = $this->getWebsiteId($storeId);
        $stockId = $this->getStockId($websiteId);

        $select = $this->getConnection()->select()
            ->from(
                ['ciss' => $this->getTable('cataloginventory_stock_status')],
                ['product_id', 'stock_status', 'qty']
            )
            ->where('ciss.stock_id = ?', $stockId)
            ->where('ciss.website_id = ?', $this->stockConfiguration->getDefaultScopeId())
            ->where('ciss.product_id IN(?)', $productIds);

        return $this->getConnection()->fetchAll($select);
    }

    /**
     * Retrieve stock_id by store
     *
     * @param $websiteId
     * @return int
     */
    private function getStockId($websiteId)
    {
        if (!isset($this->stockIdByWebsite[$websiteId])) {
            $stockId = $this->stockRegistry->getStock($websiteId)->getStockId();
            $this->stockIdByWebsite[$websiteId] = $stockId;
        }

        return $this->stockIdByWebsite[$websiteId];
    }

    /**
     * Retrieve Website Id by Store Id
     *
     * @param $storeId
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getWebsiteId($storeId)
    {
        return $this->storeManager->getStore($storeId)->getWebsiteId();
    }
}