<?php
/**
 * Copyright (c) 2019 Unbxd Inc.
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
	const DATA_SOURCE_CODE = 'price';
	
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
        $priceData = $this->resourceModel->loadPriceData($storeId, array_keys($indexData));
        $indexedFields = [];
        foreach ($priceData as $priceDataRow) {
            $productId = (int) $priceDataRow['entity_id'];
            $productTypeId = $indexData[$productId]['type_id'];
            /** @var PriceReaderInterface $priceModifier */
            $priceReader = $this->getPriceReader($productTypeId);

            $price = $priceReader->getPrice($priceDataRow);
            $originalPrice = $priceReader->getOriginalPrice($priceDataRow);
            $indexData[$productId]['price'] = $price;

            if (!in_array('price', $indexedFields)) {
                $indexedFields[] = 'price';
            }
            if (!in_array('original_price', $indexedFields)) {
                $indexedFields[] = 'original_price';
            }

            $includeOriginal = (bool) ($price != $originalPrice);
            if ($includeOriginal) {
                $indexData[$productId]['original_price'] = $originalPrice;
            }
            if (!isset($indexData[$productId]['indexed_attributes'])) {
                $indexData[$productId]['indexed_attributes'] = ['price'];
                $indexData[$productId]['indexed_attributes'] = ['original_price'];
            } else {
                if (!in_array('price', $indexData[$productId]['indexed_attributes'])) {
                    $indexData[$productId]['indexed_attributes'][] = 'price';
                }
                if (
                    $includeOriginal
                    && !in_array('original_price', $indexData[$productId]['indexed_attributes'])
                ) {
                    $indexData[$productId]['indexed_attributes'][] = 'original_price';
                }
            }
        }

        $this->attributeHelper->appendSpecificIndexedFields($indexData, $indexedFields);

        return $indexData;
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