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
namespace Unbxd\ProductFeed\Model\ResourceModel\Eav\Indexer;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Indexer\Table\StrategyInterface;
use Magento\Store\Model\StoreManagerInterface;
use Unbxd\ProductFeed\Helper\Data as ConfigHelper;

/**
 * Class provides util methods used by Eav indexer related resource models.
 *
 * Class AbstractIndexer
 * @package Unbxd\ProductFeed\Model\ResourceModel\Eav\Indexer
 */
abstract class AbstractIndexer
{
    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var AdapterInterface
     */
    protected $connection;

    /**
     * @var StrategyInterface
     */
    protected $tableStrategy;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ConfigHelper;
     */
    protected $configHelper;

    /**
     * AbstractIndexer constructor.
     * @param ResourceConnection $resource
     * @param ConfigHelper $configHelper
     * @param StrategyInterface $tableStrategy
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ResourceConnection $resource,
        StrategyInterface $tableStrategy,
        StoreManagerInterface $storeManager,
        ConfigHelper $configHelper
    ) {
        $this->resource = $resource;
        $this->configHelper=$configHelper;
        $this->connection = $resource->getConnection($configHelper->getReaderConnectionName());
        $this->tableStrategy = $tableStrategy;
        $this->storeManager = $storeManager;
    }

    /**
     * Get table name using the adapter.
     *
     * @param $tableName
     * @return string
     */
    protected function getTable($tableName)
    {
        return $this->resource->getTableName($tableName);
    }

    /**
     * Return database connection.
     *
     * @return AdapterInterface
     */
    protected function getConnection()
    {
        return $this->connection;
    }

    /**
     * Get Table strategy
     *
     * @return StrategyInterface
     */
    public function getTableStrategy()
    {
        return $this->tableStrategy;
    }

    /**
     * Retrieve index table name
     *
     * @param null $table
     * @return string
     */
    public function getIdxTable($table = null)
    {
        return $this->getTableStrategy()->getTableName($table);
    }

    /**
     * Get store by id.
     *
     * @param $storeId
     * @return \Magento\Store\Api\Data\StoreInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getStore($storeId)
    {
        return $this->storeManager->getStore($storeId);
    }

    /**
     * Retrieve store root category id.
     *
     * @param $store
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getRootCategoryId($store)
    {
        if (is_numeric($store) || is_string($store)) {
            $store = $this->getStore($store);
        }

        $storeGroupId = $store->getStoreGroupId();

        return $this->storeManager->getGroup($storeGroupId)->getRootCategoryId();
    }
}