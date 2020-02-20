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

use Unbxd\ProductFeed\Model\ResourceModel\Eav\Indexer\AbstractIndexer;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Indexer\Table\StrategyInterface;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Indexer
 * @package Unbxd\ProductFeed\Model\ResourceModel\Eav\Indexer
 */
class Indexer extends AbstractIndexer
{
    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * Indexer constructor.
     * @param ResourceConnection $resource
     * @param StrategyInterface $tableStrategy
     * @param StoreManagerInterface $storeManager
     * @param MetadataPool $metadataPool
     */
    public function __construct(
        ResourceConnection $resource,
        StrategyInterface $tableStrategy,
        StoreManagerInterface $storeManager,
        MetadataPool $metadataPool
    ) {
        parent::__construct(
            $resource,
            $tableStrategy,
            $storeManager
        );
        $this->metadataPool = $metadataPool;
    }

    /**
     * Retrieve Metadata for an entity by entity type
     *
     * @param $entityType
     * @return EntityMetadataInterface
     * @throws \Exception
     */
    protected function getEntityMetaData($entityType)
    {
        return $this->metadataPool->getMetadata($entityType);
    }
}
