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

use Magento\Catalog\Api\Data\CategoryAttributeInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Eav\Model\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Store\Model\StoreManagerInterface;
use Unbxd\ProductFeed\Model\ResourceModel\Eav\Indexer\Indexer;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Indexer\Table\StrategyInterface;
use Unbxd\ProductFeed\Logger\LoggerInterface;

/**
 * Categories data source resource model.
 *
 * Class Category
 * @package Unbxd\ProductFeed\Model\ResourceModel\Indexer\Product\Full\DataSourceProvider
 */
class Category extends Indexer
{
    /**
     * Local cache for category data
     *
     * @var array
     */
    private $categoryDataCache = [];

    /**
     * @var null|CategoryAttributeInterface
     */
    private $categoryNameAttribute = null;

    /**
     * @var null|categoryActiveFlagAttribute
     */
    private $categoryActiveFlagAttribute = null;

    /**
     * @var null|CategoryAttributeInterface
     */
    private $categoryUrlKeyAttribute = null;

    /**
     * @var null|CategoryAttributeInterface
     */
    private $categoryUrlPathAttribute = null;

    /**
     * @var \Magento\Eav\Model\Config
     */
    private $eavConfig = null;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Category constructor.
     * @param ResourceConnection $resource
     * @param StrategyInterface $tableStrategy
     * @param StoreManagerInterface $storeManager
     * @param MetadataPool $metadataPool
     * @param Config $eavConfig
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        ResourceConnection $resource,
        StrategyInterface $tableStrategy,
        StoreManagerInterface $storeManager,
        MetadataPool $metadataPool,
        Config $eavConfig,
        ObjectManagerInterface $objectManager,
        LoggerInterface $logger
    ) {
        $this->eavConfig = $eavConfig;
        $this->objectManager = $objectManager;
        $this->logger = $logger->create("feed");
        parent::__construct(
            $resource,
            $tableStrategy,
            $storeManager,
            $metadataPool
        );
    }

    /**
     * Load categories data for a list of product ids and a given store.
     *
     * @param $storeId
     * @param $productIds
     * @return array
     * @throws \Exception
     */
    public function loadCategoryData($storeId, $productIds)
    {
        $select = $this->getCategoryProductSelect($productIds, $storeId);
        $categoryData = $this->getConnection()->fetchAll($select);

        $categoryIds = [];
        foreach ($categoryData as $categoryDataRow) {
            $categoryId = isset($categoryDataRow['category_id']) ? (int) $categoryDataRow['category_id'] : '';
            if ($categoryId) {
                $categoryIds[] = $categoryId;
            }
        }

        $storeCategoryData = $this->buildCategoryData(array_unique($categoryIds), $storeId);
        foreach ($categoryData as $key => &$categoryDataRow) {
            if (isset($storeCategoryData[$categoryDataRow['category_id']])) {
                $id = (int) $categoryDataRow['category_id'];
                if (empty($storeCategoryData[$id])) {
                    unset($categoryData[$key]);
                    continue;
                }

                $categoryDataRow['name'] = $storeCategoryData[$id]['name'];
                $categoryDataRow['url_key'] = $storeCategoryData[$id]['url_key'];
                $categoryDataRow['url_path'] = $storeCategoryData[$id]['url_path'];
            }else{
                unset($categoryData[$key]);
            }
        }

        return $categoryData;
    }

    /**
     * Prepare indexed data select.
     *
     * @param array $productIds
     * @param integer $storeId
     * @return \Zend_Db_Select
     */
    protected function getCategoryProductSelect($productIds, $storeId)
    {
        $select = $this->getConnection()->select()
            ->from(['cpi' => $this->getTable($this->getCatalogCategoryProductIndexTable($storeId))])
            ->where('cpi.store_id = ?', $storeId)
            ->where('cpi.product_id IN (?)', $productIds)
            ->reset(\Magento\Framework\DB\Select::COLUMNS)
            ->columns(['category_id', 'product_id', 'is_parent']);

        return $select;
    }

    /**
     * Access to EAV configuration.
     *
     * @return \Magento\Eav\Model\Config
     */
    protected function getEavConfig()
    {
        return $this->eavConfig;
    }

    /**
     * Returns category name attribute
     *
     * @return CategoryAttributeInterface|\Magento\Eav\Model\Entity\Attribute\AbstractAttribute|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getCategoryNameAttribute()
    {
        if (null === $this->categoryNameAttribute) {
            $this->categoryNameAttribute = $this->eavConfig->getAttribute(
                \Magento\Catalog\Model\Category::ENTITY,
                'name'
            );
        }

        return $this->categoryNameAttribute;
    }


    /**
     * Returns category name attribute
     *
     * @return CategoryAttributeInterface|\Magento\Eav\Model\Entity\Attribute\AbstractAttribute|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getCategoryActiveFlagAttribute()
    {
        if (null === $this->categoryActiveFlagAttribute) {
            $this->categoryActiveFlagAttribute = $this->eavConfig->getAttribute(
                \Magento\Catalog\Model\Category::ENTITY,
                'is_active'
            );
        }

        return $this->categoryActiveFlagAttribute;
    }

    /**
     * Returns category url key attribute
     *
     * @return CategoryAttributeInterface|\Magento\Eav\Model\Entity\Attribute\AbstractAttribute|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getCategoryUrlKeyAttribute()
    {
        if (null === $this->categoryUrlKeyAttribute) {
            $this->categoryUrlKeyAttribute = $this->eavConfig->getAttribute(
                \Magento\Catalog\Model\Category::ENTITY,
                'url_key'
            );
        }

        return $this->categoryUrlKeyAttribute;
    }

    /**
     * Returns category url path attribute
     *
     * @return CategoryAttributeInterface|\Magento\Eav\Model\Entity\Attribute\AbstractAttribute|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getCategoryUrlPathAttribute()
    {
        if (null === $this->categoryUrlPathAttribute) {
            $this->categoryUrlPathAttribute = $this->eavConfig->getAttribute(
                \Magento\Catalog\Model\Category::ENTITY,
                'url_path'
            );
        }

        return $this->categoryUrlPathAttribute;
    }

    /**
     * Get category product index table name.
     *
     * @param $storeId
     * @return string
     */
    protected function getCatalogCategoryProductIndexTable($storeId)
    {
        // init table name as legacy table name.
        $indexTable = $this->getTable('catalog_category_product_index');
        try {
            // retrieve table name for the current store Id from the TableMaintainer.
            // class TableMaintainer encapsulate logic of work with tables per store in related indexer
            if (class_exists(\Magento\Catalog\Model\Indexer\Category\Product\TableMaintainer::class)) {
                $tableMaintainer = $this->objectManager->get(
                    \Magento\Catalog\Model\Indexer\Category\Product\TableMaintainer::class
                );
                $indexTable = $tableMaintainer->getMainTable($storeId);
            }
        } catch (\Exception $e) {
            // occurs in Magento version where TableMaintainer is not implemented, will default to legacy table.
        }

        return $indexTable;
    }

    /**
     * Add some categories data into the cache of categories.
     *
     * @param $categoryIds
     * @param $storeId
     * @return array|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function buildCategoryData($categoryIds, $storeId)
    {
        $loadCategoryIds = $categoryIds;

        if (isset($this->categoryDataCache[$storeId])) {
            $loadCategoryIds = array_diff($categoryIds, array_keys($this->categoryDataCache[$storeId]));
        }

        $loadCategoryIds = array_map('intval', $loadCategoryIds);
        if (!empty($loadCategoryIds)) {
            $select = $this->prepareCategoryDataSelect($loadCategoryIds, $storeId);
            $entityIdField = $this->getEntityMetaData(CategoryInterface::class)->getIdentifierField();

            foreach ($this->getConnection()->fetchAll($select) as $row) {

                $categoryId = isset($row[$entityIdField]) ? (int) $row[$entityIdField] : false;
                if (!$categoryId) {
                    continue;
                }
                if (isset($row["is_active"]) && $row["is_active"]) {
                    $this->categoryDataCache[$storeId][$categoryId] = $row;
                } else {
                    $this->logger->info($categoryId . "is disabled , hence will not be updated in product feed");
                }
            }
        }

        return isset($this->categoryDataCache[$storeId]) ? $this->categoryDataCache[$storeId] : [];
    }

    /**
     * Prepare SQL query to retrieve category data
     *
     * @param $loadCategoryIds
     * @param $storeId
     * @return \Magento\Framework\DB\Select
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function prepareCategoryDataSelect($loadCategoryIds, $storeId)
    {
        $rootCategoryId = (int) $this->storeManager->getStore($storeId)->getRootCategoryId();
        $this->categoryDataCache[$storeId][$rootCategoryId] = '';

        $entityIdField = $this->getEntityMetaData(CategoryInterface::class)->getIdentifierField();
        $linkField = $this->getEntityMetaData(CategoryInterface::class)->getLinkField();
        $nameAttr = $this->getCategoryNameAttribute();
        $urlKeyAttr = $this->getCategoryUrlKeyAttribute();
        $urlPathAttr = $this->getCategoryUrlPathAttribute();
        $is_ActiveAttr = $this->getCategoryActiveFlagAttribute();

        $select = $this->connection->select();

        $joinConditions = [];
        $resultColumns = [];
        // prepend entity ID field to result columns
        $resultColumns[] = $entityIdField;
        foreach ([$nameAttr, $urlKeyAttr, $urlPathAttr, $is_ActiveAttr] as $attribute) {
            // build join conditions
            $attributeCode = $attribute->getAttributeCode();
            $conditions = [
                "{$attributeCode}_default.$linkField = {$attributeCode}_store.{$linkField}",
                "{$attributeCode}_default.attribute_id = {$attributeCode}_store.attribute_id",
                "{$attributeCode}_store.store_id = ?",
            ];

            $joinStoreValuesCondition = $this->connection->quoteInto(
                implode(" AND ", $conditions),
                $storeId
            );

            $joinConditions[$attribute->getAttributeId()] = [
                'attribute_code' => $attributeCode,
                'table' => (string) $attribute->getBackendTable(),
                'conditions' => new \Zend_Db_Expr($joinStoreValuesCondition),
            ];

            // build result columns, if store value is not specify, get default value
            $resultColumns[] = new \Zend_Db_Expr(
                "COALESCE({$attributeCode}_store.value, {$attributeCode}_default.value, NULL) AS {$attributeCode}"
            );
        }

        $select->distinct(
            true
        )->from(
            ['cat' => $this->getEntityMetaData(CategoryInterface::class)->getEntityTable()],
            $resultColumns
        );

        // apply join conditions
        foreach ($joinConditions as $attributeId => $conditions) {
            $attributeCode = $conditions['attribute_code'];

            $select->joinInner(
                ["{$attributeCode}_default" => $conditions['table']],
                new \Zend_Db_Expr("cat.{$linkField} = {$attributeCode}_default.{$linkField}"),
                []
            )
                ->joinLeft(["{$attributeCode}_store" => $conditions['table']], $conditions['conditions'], [])
                ->where("{$attributeCode}_default.store_id = ?", 0)
                ->where("{$attributeCode}_default.attribute_id = ?", $attributeId);
        }

        $select->where("cat.$entityIdField != ?", $rootCategoryId)
            ->where("cat.$entityIdField IN (?)", $loadCategoryIds);


        return $select;
    }
}
