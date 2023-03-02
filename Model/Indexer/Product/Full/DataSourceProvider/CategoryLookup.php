<?php
/**
 * Copyright (c) 2020 Unbxd Inc.
 */

/**
 * Enhancement development:
 * @author jags
 * @email jagadeesh@oceaniasolution.com
 * @team Oceania
 */
namespace Unbxd\ProductFeed\Model\Indexer\Product\Full\DataSourceProvider;

use Magento\Catalog\Model\Product;
use \Magento\Eav\Api\Data\AttributeInterface;
use Unbxd\ProductFeed\Model\Indexer\Product\Full\DataSourceProviderInterface;
use Unbxd\ProductFeed\Helper\AttributeHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Unbxd\ProductFeed\Logger\LoggerInterface;
use Unbxd\ProductFeed\Helper\Data as HelperData;
use Exception;
use Unbxd\ProductFeed\Model\Feed\Config;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;

/**
 * Data source used to append categories data to product whe category indexer is not working in ur environment.
 *
 * Class Category
 * @package Unbxd\ProductFeed\Model\Indexer\Product\Full\DataSourceProvider
 */
class CategoryLookup implements DataSourceProviderInterface
{
    /**
     * Related data source code
     */
	const DATA_SOURCE_CODE = 'category-lookup';
	
    protected $productRepository;

    /**
     * @var AttributeHelper
     */
    private $attributeHelper;

     /**
     * @var HelperData
     */
    private $helperData;

     /** Define the schema for the attribute */

     /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CategoryCollectionFactory
     */
    private $categoryCollectionFactory;


    /**
     * CategoryLookup constructor.
     * @param ProductRepositoryInterface $productRepository
     * @param LoggerInterface $logger
     * @param HelperData $helperData
     * @param AttributeHelper $attributeHelper
     */
    public function __construct(ProductRepositoryInterface $productRepository,
        LoggerInterface $logger, 
        HelperData $helperData,
        AttributeHelper $attributeHelper,
        CategoryCollectionFactory $categoryCollectionFactory
    ) {
        $this->productRepository = $productRepository;
        $this->logger = $logger->create("feed");
        $this->attributeHelper = $attributeHelper;
        $this->helperData = $helperData;
        $this->categoryCollectionFactory = $categoryCollectionFactory;

    }

    /**
     * {@inheritdoc}
     */
    public function getDataSourceCode()
    {
        return self::DATA_SOURCE_CODE;
    }

    private function getCategoryCollection($productId){
        $collection = $this->categoryCollectionFactory->create();
        $collection->joinField(
            'product_id',
            'catalog_category_product',
            'product_id',
            'category_id = entity_id',
            null
        )->addFieldToFilter(
            'product_id',
            (int)$productId
        );
        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function appendData($storeId, array $indexData)
    {
        if(!$this->helperData->fetchFromCategoryTable($storeId)){
            return $indexData;
        }
        foreach (array_keys($indexData) as $productId) {
            try {
                if ($productId != "fields"){

                        $categoryCollection = $this->getCategoryCollection($productId);
                        if($storeId){
                            $categoryCollection->setStoreId($storeId);
                        }
                        $categoryCollection->addFieldToSelect("name")->addFieldToSelect("is_active")->addFieldToSelect("url_path")->addFieldToSelect("is_parent");
                        $categoryDataList = array();
                        /** @var \Magento\Catalog\Model\Category $category */
                        foreach ($categoryCollection->getItems() as $category){
                            $categoryData = [
                                "category_id" => $category->getId(),
                                "id_path" => $category->getPath(),
                                "name" => $category->getName(),
                                "url_key" => $category->getUrlKey(),
                                "url_path" => $category->getUrlPath(),
                                "is_active" => $category->getIsActive()
                            ];
                            $categoryDataList[] = array_filter($categoryData,'Unbxd\\ProductFeed\\Helper\\HelperUtil::_nonNull');
                        }
                        $indexData[$productId]["category"] = $categoryDataList;
                }
            }catch (\Exception $e) {
                $this->logger->error('Error while fetching category data -'.$productId. $e->__toString());
            }
        }

        $this->attributeHelper->appendSpecificIndexedFields($indexData, ['category',Config::FIELD_UNBXD_CATEGORY_PATH]);

        return $indexData;
    }
    
}
