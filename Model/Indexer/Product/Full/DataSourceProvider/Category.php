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
use Unbxd\ProductFeed\Model\ResourceModel\Indexer\Product\Full\DataSourceProvider\Category as ResourceModel;
use Unbxd\ProductFeed\Helper\AttributeHelper;
use Unbxd\ProductFeed\Helper\Data as HelperData;

/**
 * Data source used to append categories data to product during indexing.
 *
 * Class Category
 * @package Unbxd\ProductFeed\Model\Indexer\Product\Full\DataSourceProvider
 */
class Category implements DataSourceProviderInterface
{
    /**
     * Related data source code
     */
	const DATA_SOURCE_CODE = 'category';
	
    /**
     * @var ResourceModel
     */
    private $resourceModel;

    /**
     * @var AttributeHelper
     */
    private $attributeHelper;

    /**
     * @var HelperData
     */
    private $helperData;

    /**
     * Category constructor.
     * @param ResourceModel $resourceModel
     * @param AttributeHelper $attributeHelper
     */
    public function __construct(
        ResourceModel $resourceModel,
        AttributeHelper $attributeHelper,
        HelperData $helperData
    ) {
        $this->resourceModel = $resourceModel;
        $this->attributeHelper = $attributeHelper;
        $this->helperData = $helperData;
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

        if($this->helperData->fetchFromCategoryTable($storeId)){
            return $indexData;
        }
        $categoryData = $this->resourceModel->loadCategoryData($storeId, array_keys($indexData));
        $indexedFields = [];
        foreach ($categoryData as $categoryDataRow) {
            $productId = (int) $categoryDataRow['product_id'];
            unset($categoryDataRow['product_id']);

            $categoryDataRow = array_merge(
                $categoryDataRow,
                [
                    'category_id' => (int) $categoryDataRow['category_id']
                ]
            );

            if (isset($categoryDataRow['position']) && $categoryDataRow['position'] !== null) {
                $categoryDataRow['position'] = (int) $categoryDataRow['position'];
            }

            $indexData[$productId]['category'][] = array_filter($categoryDataRow,'Unbxd\\ProductFeed\\Helper\\HelperUtil::_nonNull');
            if (!in_array('category', $indexedFields)) {
                $indexedFields[] = 'category';
            }
        }

        $this->attributeHelper->appendSpecificIndexedFields($indexData, $indexedFields);

        return $indexData;
    }

    
}
