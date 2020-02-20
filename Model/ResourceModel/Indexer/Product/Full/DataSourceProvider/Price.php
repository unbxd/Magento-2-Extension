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

/**
 * Prices data data source resource model.
 *
 * Class Price
 * @package Unbxd\ProductFeed\Model\ResourceModel\Indexer\Product\Full\DataSourceProvider
 */
class Price extends Indexer
{
    /**
     * @var null
     */
    private $priceIndexer = null;

    /**
     * @var int
     */
    private $limiter = 0;

    /**
     * Load prices data for a list of product ids and a given store.
     *
     * @param $storeId
     * @param $productIds
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function loadPriceData($storeId, $productIds)
    {
        $websiteId = $this->getStore($storeId)->getWebsiteId();

        // check if entities data exist in price index table
        $select = $this->getConnection()->select()
            ->from(['p' => $this->getTable('catalog_product_index_price')])
            ->where('p.customer_group_id = ?', 0) // for all customers
            ->where('p.website_id = ?', $websiteId)
            ->where('p.entity_id IN (?)', $productIds);

        $result = $this->getConnection()->fetchAll($select);
		
		return $result;

        if ($this->limiter > 3) {
            return $result;
        }

        // new added product prices may not be populated into price index table in some reason,
        // try to force reindex for unprocessed entities
        $processedIds = [];
        foreach ($result as $priceData) {
            $processedIds[] = $priceData['entity_id'];
        }
        $diffIds = array_diff($productIds, $processedIds);
        if (!empty($diffIds)) {
            $this->getPriceIndexer()->executeList($diffIds);
            $this->limiter += 1;
            $this->loadPriceData($storeId, $productIds);
        }

        return $result;
    }

    /**
     * @return \Magento\Catalog\Model\Indexer\Product\Price
     */
    private function getPriceIndexer()
    {
        if ($this->priceIndexer == null) {
            $this->priceIndexer = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Catalog\Model\Indexer\Product\Price::class);
        }

        return $this->priceIndexer;
    }
}
