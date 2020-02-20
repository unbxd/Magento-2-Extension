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

use Unbxd\ProductFeed\Model\ResourceModel\Eav\Indexer\Indexer;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ResourceModel\Product\Gallery;
use Magento\Framework\Indexer\Table\StrategyInterface;

/**
 * Product images data source resource model
 *
 * Class Inventory
 * @package Unbxd\ProductFeed\Model\ResourceModel\Indexer\Product\Full\DataSourceProvider
 */
class Image extends Indexer
{
    /**
     * Image constructor.
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
            $storeManager,
            $metadataPool
        );
    }

    /**
     * Load images data for a list of product ids and a given store.
     *
     * @param $storeId
     * @param $productIds
     * @return array
     */
    public function loadImagesData($storeId, $productIds)
    {
        $bind = [];
        $stores = array_unique([Store::DEFAULT_STORE_ID, $storeId]);
        $bind['media_type'] = 'image';
        $select = $this->getConnection()->select()
            ->from(
            ['cpemgv' => $this->getTable(Gallery::GALLERY_VALUE_TABLE)],
            ['entity_id as product_id', 'position']
        )
        ->joinLeft(
            ['cpemg' => $this->getTable(Gallery::GALLERY_TABLE)],
            "cpemgv.value_id = cpemg.value_id",
            ['value as filepath', 'disabled']
        )
        ->where('cpemgv.store_id IN (?)', $stores)
        ->where('cpemgv.entity_id IN (?)', $productIds)
        ->where('cpemg.media_type = :media_type');

        return $this->getConnection()->fetchAll($select, $bind);
    }
}