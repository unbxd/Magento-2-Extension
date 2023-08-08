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
namespace Unbxd\ProductFeed\Model\Indexer\Product\Full\DataSourceProvider;

use Unbxd\ProductFeed\Model\Indexer\Product\Full\DataSourceProviderInterface;
use Unbxd\ProductFeed\Model\ResourceModel\Indexer\Product\Full\DataSourceProvider\Price as ResourceModel;
use Unbxd\ProductFeed\Model\Indexer\Product\Full\DataSourceProvider\Price\PriceReaderInterface;
use Unbxd\ProductFeed\Helper\AttributeHelper;

/**
 * Data source used to append prices data to product during indexing.
 *
 * Class Price
 * @package Unbxd\ProductFeed\Model\Indexer\Product\Full\DataSourceProvider
 */
class Price implements DataSourceProviderInterface
{
    /**
     * Related data source code
     */
	const DATA_SOURCE_CODE = "price";
	
    /**
     * @var ResourceModel
     */
    private $resourceModel;

    /**
     * @var AttributeHelper
     */
    private $attributeHelper;

    /**
     * @var PriceReaderInterface[]
     */
    private $priceReaderPool = [];

    /**
     * Price constructor.
     * @param ResourceModel $resourceModel
     * @param AttributeHelper $attributeHelper
     * @param array $priceReaderPool
     */
    public function __construct(
        ResourceModel $resourceModel,
        AttributeHelper $attributeHelper,
        $priceReaderPool = []
    ) {
        $this->resourceModel = $resourceModel;
        $this->attributeHelper = $attributeHelper;
        $this->priceReaderPool = $priceReaderPool;
    }
	
    /**
     * {@inheritdoc}
     */
	public function getDataSourceCode()
	{
		return self::DATA_SOURCE_CODE;
	}

    /**
     * {@inheritdoc}
     */
    public function appendData($storeId, array $indexData)
    {
        
        $indexedFields = [];
        
        $this->appendPriceData($storeId,$indexData,$indexedFields);
        $stores = $this->attributeHelper->getMultiStoreEnabledStores();
        foreach($stores as $multiStoreId){
            $this->appendPriceData($multiStoreId,$indexData,$indexedFields,true);
        }
        $this->attributeHelper->appendSpecificIndexedFields($indexData, $indexedFields);

        return $indexData;
    }

    private function appendPriceData($storeId, array &$indexData,array &$indexedFields, $multiStore=false)
    {
        $priceData = $this->resourceModel->loadPriceData($storeId, array_keys($indexData));
        foreach ($priceData as $priceDataRow) {
            $productId = (int) $priceDataRow['entity_id'];
            $productTypeId = $indexData[$productId]['type_id'];
            /** @var PriceReaderInterface $priceModifier */
            $priceReader = $this->getPriceReader($productTypeId);

            $price = $priceReader->getPrice($priceDataRow);
            $originalPrice = $priceReader->getOriginalPrice($priceDataRow);
            $priceAttribute = $multiStore ? "price_store_".$storeId : "price";
            $originalPriceAttribute = $multiStore ? "original_price_store_".$storeId : "original_price";
            $indexData[$productId][$priceAttribute] = $price;

            if (!in_array($priceAttribute, $indexedFields)) {
                $indexedFields[] = $priceAttribute;
            }
            if (!in_array($originalPriceAttribute, $indexedFields)) {
                $indexedFields[] = $originalPriceAttribute;
            }

            $includeOriginal = (bool) ($price != $originalPrice);
            if ($includeOriginal) {
                $indexData[$productId][$originalPriceAttribute] = $originalPrice;
            }
            if (!isset($indexData[$productId]['indexed_attributes'])) {
                $indexData[$productId]['indexed_attributes'] = [$priceAttribute];
                $indexData[$productId]['indexed_attributes'] = [$originalPriceAttribute];
            } else {
                if (!in_array($priceAttribute, $indexData[$productId]['indexed_attributes'])) {
                    $indexData[$productId]['indexed_attributes'][] = $priceAttribute;
                }
                if (
                    $includeOriginal
                    && !in_array($originalPriceAttribute, $indexData[$productId]['indexed_attributes'])
                ) {
                    $indexData[$productId]['indexed_attributes'][] = $originalPriceAttribute;
                }
            }
        }
    }

    /**
     * Retrieve price
     *
     * @param $typeId
     * @return mixed|PriceReaderInterface
     */
    private function getPriceReader($typeId)
    {
        $priceModifier = $this->priceReaderPool['default'];
        if (isset($this->priceReaderPool[$typeId])) {
            $priceModifier = $this->priceReaderPool[$typeId];
        }

        return $priceModifier;
    }
}