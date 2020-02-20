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
namespace Unbxd\ProductFeed\Model\ResourceModel\Indexer\Product\Full\Action;

use Unbxd\ProductFeed\Model\Config\Source\FilterAttribute;
use Unbxd\ProductFeed\Model\ResourceModel\Eav\Indexer\Indexer;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Indexer\Table\StrategyInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Config as ResourceModelCatalogConfig;
use Unbxd\ProductFeed\Helper\Data as HelperData;
use Unbxd\ProductFeed\Model\FilterAttribute\FilterAttributeInterface;
use Unbxd\ProductFeed\Model\FilterAttribute\Attributes\Status as FilterAttributeStatus;
use Unbxd\ProductFeed\Model\FilterAttribute\Attributes\Inventory as FilterAttributeInventory;
use Unbxd\ProductFeed\Model\FilterAttribute\Attributes\Visibility as FilterAttributeVisibility;
use Unbxd\ProductFeed\Model\FilterAttribute\Attributes\Image as FilterAttributeImage;

/**
 * Unbxd product full indexer resource model.
 *
 * Class Full
 * @package Unbxd\ProductFeed\Model\ResourceModel\Indexer\Product\Full\Action
 */
class Full extends Indexer
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var ResourceModelCatalogConfig
     */
    private $resourceModelCatalogConfig;

    /**
     * @var HelperData
     */
    private $helperData;

    /**
     * @var null|string
     */
    private $entityType = null;

    /**
     * Full constructor.
     * @param ResourceConnection $resource
     * @param StrategyInterface $tableStrategy
     * @param StoreManagerInterface $storeManager
     * @param MetadataPool $metadataPool
     * @param ObjectManagerInterface $objectManager
     * @param ResourceModelCatalogConfig $resourceModelCatalogConfig
     * @param HelperData $helperData
     * @param null $entityType
     */
    public function __construct(
        ResourceConnection $resource,
        StrategyInterface $tableStrategy,
        StoreManagerInterface $storeManager,
        MetadataPool $metadataPool,
        ObjectManagerInterface $objectManager,
        ResourceModelCatalogConfig $resourceModelCatalogConfig,
        HelperData $helperData,
        $entityType = null
    ) {
        parent::__construct(
            $resource,
            $tableStrategy,
            $storeManager,
            $metadataPool
        );
        $this->objectManager = $objectManager;
        $this->resourceModelCatalogConfig = $resourceModelCatalogConfig;
        $this->helperData = $helperData;
        $this->entityType = $entityType;
    }

    /**
     * Get entity type
     *
     * @return string
     */
    protected function getEntityType()
    {
        return $this->entityType;
    }

    /**
     * Retrieve supported product types
     *
     * @param $storeId
     * @return array
     */
    private function getSupportedProductTypes($storeId = null)
    {
        return $this->helperData->getAvailableProductTypes($storeId);
    }

    /**
     * @param $storeId
     * @return array
     */
    private function getFilterAttributes($storeId)
    {
        return $this->helperData->getFilterAttributes($storeId);
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function getEntityTable()
    {
        $metadata = $this->getEntityMetaData($this->getEntityType());
        return $this->getTable($metadata->getEntityTable());
    }

    /**
     * Retrieve product SKU by related ID
     *
     * @param $entityId
     * @return mixed
     * @throws \Exception
     */
    public function getProductSkuById($entityId)
    {
        $select = $this->getConnection()->select()
            ->from(['e' => $this->getEntityTable()])
            ->where('e.entity_id = ?', $entityId)
            ->reset(\Magento\Framework\DB\Select::COLUMNS)
            ->columns('sku');

        return $this->getConnection()->fetchOne($select);
    }

    /**
     * Load a bulk of product data.
     *
     * @param $storeId
     * @param array $productIds
     * @param int $fromId
     * @param bool $useFilters
     * @param int $limit
     * @return mixed
     * @throws \Exception
     */
    public function getProducts($storeId, $productIds = [], $fromId = 0, $useFilters = true, $limit = 10000)
    {
        $select = $this->getConnection()->select()
            ->from(['e' => $this->getEntityTable()]);

        if ($useFilters) {
            $this->addCollectionFilters($select, $storeId);
        }

        if (!empty($productIds)) {
            $select->where('e.entity_id IN (?)', $productIds);
        }

		$select->limit($limit);
        $select->where('e.entity_id > ?', $fromId);
        $select->where('e.type_id IN (?)', $this->getSupportedProductTypes($storeId));
        $select->order('e.entity_id');

        return $this->connection->fetchAll($select);
    }

    /**
     * Retrieve products relations by children
     *
     * @param $childrenIds
     * @return array
     * @throws \Exception
     */
    public function getRelationsByChild($childrenIds)
    {
        $metadata = $this->getEntityMetaData($this->getEntityType());
        $entityTable = $this->getEntityTable();
        $relationTable = $this->getTable('catalog_product_relation');
        $joinCondition = sprintf('relation.parent_id = entity.%s', $metadata->getLinkField());

        $select = $this->getConnection()->select()
            ->from(['relation' => $relationTable], [])
            ->join(['entity' => $entityTable], $joinCondition, [$metadata->getIdentifierField()])
            ->where('child_id IN (?)', array_map('intval', $childrenIds));

        return $this->getConnection()->fetchCol($select);
    }

    /**
     * Retrieve products relations by parent
     *
     * @param $parentIds
     * @return array
     * @throws \Exception
     */
    public function getRelationsByParent($parentIds)
    {
        $metadata = $this->getEntityMetaData($this->getEntityType());
        $entityTable = $this->getEntityTable();
        $relationTable = $this->getTable('catalog_product_relation');
        $joinCondition = sprintf('relation.child_id = entity.%s', $metadata->getLinkField());

        $select = $this->getConnection()->select()
            ->from(['relation' => $relationTable], [])
            ->join(['entity' => $entityTable], $joinCondition, [$metadata->getIdentifierField()])
            ->where('parent_id IN (?)', array_map('intval', $parentIds));

        return $this->getConnection()->fetchCol($select);
    }

    /**
     * Retrieve related parent product if any
     *
     * @param $childrenId
     * @return string
     * @throws \Exception
     */
    public function getRelatedParentProduct($childrenId)
    {
        $metadata = $this->getEntityMetaData($this->getEntityType());
        $entityTable = $this->getTable($metadata->getEntityTable());
        $relationTable = $this->getTable('catalog_product_relation');
        $joinCondition = sprintf('relation.parent_id = entity.%s', $metadata->getLinkField());

        $select = $this->getConnection()->select()
            ->from(['relation' => $relationTable], ['parent_id'])
            ->join(['entity' => $entityTable], $joinCondition, [$metadata->getIdentifierField()])
            ->where('child_id = ?', $childrenId)
            ->where('entity.type_id IN (?)', $this->getSupportedProductTypes());

        return $this->getConnection()->fetchOne($select);
    }

    /**
     * Filter collection by different conditions (eq.: visibility, status)
     *
     * @param $select
     * @param $storeId
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function addCollectionFilters($select, $storeId)
    {
        /** @var FilterAttributeInterface[] $filterAttributes */
        $filterAttributes = $this->getFilterAttributes($storeId);
        if (empty($filterAttributes)) {
            return $this;
        }

        foreach ($filterAttributes as $attribute) {
            /** @var FilterAttributeInterface $attributeCode */
            $attributeCode = $attribute->getAttributeCode();
            $filterValue = $attribute->getValue();
            if ($attributeCode == FilterAttributeStatus::ATTRIBUTE_CODE) {
                $this->addStatusFilter($select, $filterValue, $storeId);
            } else if ($attributeCode == FilterAttributeInventory::ATTRIBUTE_CODE) {
                $this->addStockFilter($select, $filterValue, $storeId);
            } else if ($attributeCode == FilterAttributeVisibility::ATTRIBUTE_CODE) {
                $this->addIsVisibleInStoreFilter($select, $filterValue, $storeId);
            } else if ($attributeCode == FilterAttributeImage::ATTRIBUTE_CODE) {
                $this->addImageFilter($select, $filterValue, $storeId);
            }
        }

        return $this;
    }

    /**
     * Filter the select to append only enabled product into the index.
     *
     * @param $select
     * @param $filterValue
     * @param $storeId
     * @return $this
     * @throws \Exception
     */
    private function addStatusFilter($select, $filterValue, $storeId)
    {
        $metadata = $this->getEntityMetaData($this->getEntityType());
        $linkField = $metadata->getLinkField();
        $entityTypeId = $this->resourceModelCatalogConfig->getEntityTypeId();

        $bind = ['status' => 'status'];
        $statusAttributeIdSelect = $this->getConnection()->select()
            ->from(['eav' => $this->getTable('eav_attribute')], ['attribute_id'])
            ->where('eav.entity_type_id = ?', $entityTypeId)
            ->where('eav.attribute_code = :status');

        $statusAttributeId = $this->getConnection()->fetchOne($statusAttributeIdSelect, $bind);

        $conditions = ["status.{$linkField} = e.{$linkField}"];
        $conditions[] = $this->getConnection()->quoteInto('status.value != ?', $filterValue);

        $statusJoinCond = join(' AND ', $conditions);
        $select->useStraightJoin(true)
            ->join(
                ['status' => $this->getTable('catalog_product_entity_int')],
                $statusJoinCond,
                ['value AS status']
            )
            ->where('status.attribute_id = ?', (int) $statusAttributeId);

        return $this;
    }

    /**
     * Filter the select to append only in stock product into the index.
     *
     * @param $select
     * @param $filterValue
     * @param $storeId
     * @return $this
     */
    private function addStockFilter($select, $filterValue, $storeId)
    {
        // @TODO - not implemented
        return $this;
    }

    /**
     * Filter the select to append only product visible into the catalog or search into the index.
     *
     * @param $select
     * @param $filterValue
     * @param $storeId
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function addIsVisibleInStoreFilter($select, $filterValue, $storeId)
    {
        $rootCategoryId = $this->getRootCategoryId($storeId);
        $indexTable = $this->getCatalogCategoryProductIndexTable($storeId);

        $conditions = ['visibility.product_id = e.entity_id'];
        $conditions[] = $this->getConnection()->quoteInto('visibility.store_id = ?', $storeId);
        $conditions[] = $this->getConnection()->quoteInto('visibility.visibility = ?', $filterValue);

        $visibilityJoinCond = join(' AND ', $conditions);
        $select->useStraightJoin(true)
            ->join(['visibility' => $indexTable], $visibilityJoinCond, ['visibility'])
            ->where('visibility.category_id = ?', (int) $rootCategoryId);

        return $this;
    }

    /**
     * Filter the select to append only product with images into the index.
     *
     * @param $select
     * @param $filterValue
     * @param $storeId
     * @return $this
     */
    private function addImageFilter($select, $filterValue, $storeId)
    {
        // @TODO - not implemented
        return $this;
    }

    /**
     * Retrieve product index table
     *
     * @param $storeId
     * @return string
     */
    private function getCatalogCategoryProductIndexTable($storeId)
    {
        // init table name as legacy table name
        $indexTable = $this->getTable('catalog_category_product_index');

        try {
            // try to retrieve table name for the current store Id from the TableMaintainer.
            // class TableMaintainer encapsulate logic of work with tables per store in related indexer
            if (class_exists(\Magento\Catalog\Model\Indexer\Category\Product\TableMaintainer::class)) {
                $tableMaintainer = $this->objectManager->get(
                    \Magento\Catalog\Model\Indexer\Category\Product\TableMaintainer::class
                );
                $indexTable = $tableMaintainer->getMainTable($storeId);
            }
        } catch (\Exception $exception) {
            // occurs in magento version where TableMaintainer is not implemented. Will default to legacy table.
        }

        return $indexTable;
    }
}