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
use Unbxd\ProductFeed\Model\ResourceModel\Indexer\Product\Full\DataSourceProvider\Inventory as ResourceModel;
use Unbxd\ProductFeed\Helper\AttributeHelper;

/**
 * Data source used to append inventory data to product during indexing.
 *
 * Class Inventory
 * @package Unbxd\ProductFeed\Model\Indexer\Product\Full\DataSourceProvider
 */
class Inventory implements DataSourceProviderInterface
{
    /**
     * Related data source code
     */
	const DATA_SOURCE_CODE = 'inventory';

    /**
     * @var ResourceModel
     */
    private $resourceModel;

    /**
     * @var AttributeHelper
     */
    private $attributeHelper;

    /**
     * Inventory constructor.
     * @param ResourceModel $resourceModel
     * @param AttributeHelper $attributeHelper
     */
    public function __construct(
        ResourceModel $resourceModel,
        AttributeHelper $attributeHelper
    ) {
        $this->resourceModel = $resourceModel;
        $this->attributeHelper = $attributeHelper;
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
        $inventoryData = $this->resourceModel->loadInventoryData($storeId, array_keys($indexData));
        $indexedFields = [];
        foreach ($inventoryData as $inventoryDataRow) {
            $productId = (int) $inventoryDataRow['product_id'];
            $isInStock = (bool) $inventoryDataRow['stock_status'];
            $qty = (int) $inventoryDataRow['qty'];
            $indexData[$productId]['quantity_and_stock_status'] = $isInStock;

            if (!in_array('quantity_and_stock_status', $indexedFields)) {
                $indexedFields[] = 'quantity_and_stock_status';
            }
            $indexData[$productId]['availabilityText']= ($isInStock ? "true" : "false");
            if (!in_array('availabilityText', $indexedFields)) {
                $indexedFields[] = 'availabilityText';
            }
        }

        $this->attributeHelper->appendSpecificIndexedFields($indexData, $indexedFields);

        return $indexData;
    }
}