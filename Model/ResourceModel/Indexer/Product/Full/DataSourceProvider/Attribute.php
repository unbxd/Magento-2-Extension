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

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Unbxd\ProductFeed\Model\ResourceModel\Eav\Indexer\Full\DataSourceProvider\AbstractAttribute;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Framework\Indexer\Table\StrategyInterface;

/**
 * Attribute data source resource model.
 *
 * Class Attribute
 * @package Unbxd\ProductFeed\Model\ResourceModel\Indexer\Product\Full\DataSourceProvider
 */
class Attribute extends AbstractAttribute
{
    /**
     * Catalog product type
     *
     * @var \Magento\Catalog\Model\Product\Type
     */
    private $catalogProductType;

    /**
     * @var \Magento\Catalog\Model\Product\Type[]
     */
    private $productTypes = [];

    /**
     * @var array
     */
    private $productEmulators = [];

    /**
     * Attribute constructor.
     * @param ResourceConnection $resource
     * @param StrategyInterface $tableStrategy
     * @param StoreManagerInterface $storeManager
     * @param MetadataPool $metadataPool
     * @param ProductType $catalogProductType
     * @param string $entityType
     */
    public function __construct(
        ResourceConnection $resource,
        StrategyInterface $tableStrategy,
        StoreManagerInterface $storeManager,
        MetadataPool $metadataPool,
        ProductType $catalogProductType,
        $entityType = ProductInterface::class
    ) {
        parent::__construct(
            $resource,
            $tableStrategy,
            $storeManager,
            $metadataPool,
            $entityType
        );
        $this->catalogProductType = $catalogProductType;
    }

    /**
     * List of composite product types.
     *
     * @return array
     */
    public function getCompositeTypes()
    {
        return $this->catalogProductType->getCompositeTypes();
    }

    /**
     * Retrieve list of children ids for a product list.
     * The result use children ids as a key and list of parents as value
     *
     * @param $productIds
     * @param $storeId
     * @return array
     * @throws \Exception
     */
    public function loadChildren($productIds, $storeId)
    {
        $children = [];
        foreach ($this->catalogProductType->getCompositeTypes() as $productTypeId) {
            $typeInstance = $this->getProductTypeInstance($productTypeId);
            $relation = $typeInstance->getRelationInfo();
            if ($relation->getTable() && $relation->getParentFieldName() && $relation->getChildFieldName()) {
                $select = $this->getRelationQuery($relation, $productIds, $storeId);
                $data = $this->getConnection()->fetchAll($select);
                foreach ($data as $relationRow) {
                    $parentId = (int) $relationRow['parent_id'];
                    $childId = (int) $relationRow['child_id'];
                    $sku = (string) $relationRow['sku'];
                    $configurableAttributes = array_filter(
                        explode(',', $relationRow['configurable_attributes'])
                    );
                    $children[$childId][] = [
                        'parent_id' => $parentId,
                        'configurable_attributes' => $configurableAttributes,
                        'sku' => $sku,
                    ];
                }
            }
        }

        return $children;
    }

    /**
     * Retrieve product emulator (magento data object) by type identifier.
     *
     * @param $typeId
     * @return mixed
     */
    protected function getProductEmulator($typeId)
    {
        if (!isset($this->productEmulators[$typeId])) {
            $productEmulator = new \Magento\Framework\DataObject();
            $productEmulator->setTypeId($typeId);
            $this->productEmulators[$typeId] = $productEmulator;
        }

        return $this->productEmulators[$typeId];
    }

    /**
     * Retrieve product type instance from identifier.
     *
     * @param $typeId
     * @return ProductType
     */
    protected function getProductTypeInstance($typeId)
    {
        if (!isset($this->productTypes[$typeId])) {
            $productEmulator = $this->getProductEmulator($typeId);
            $this->productTypes[$typeId] = $this->catalogProductType->factory($productEmulator);
        }

        return $this->productTypes[$typeId];
    }

    /**
     * Get Entity Id used by this indexer
     *
     * @return string
     */
    protected function getEntityTypeId()
    {
        return ProductInterface::class;
    }

    /**
     * @param $relation
     * @param $parentIds
     * @param $storeId
     * @return \Magento\Framework\DB\Select
     * @throws \Exception
     */
    private function getRelationQuery($relation, $parentIds, $storeId)
    {
        $linkField = $this->getEntityMetaData($this->getEntityTypeId())->getLinkField();
        $entityIdField = $this->getEntityMetaData($this->getEntityTypeId())->getIdentifierField();
        $entityTable = $this->getTable($this->getEntityMetaData($this->getEntityTypeId())->getEntityTable());
        $relationTable = $this->getTable($relation->getTable());
        $parentFieldName = $relation->getParentFieldName();
        $childFieldName = $relation->getChildFieldName();

        $select = $this->getConnection()->select()
            ->from(['main' => $relationTable], [])
            ->joinInner(
                ['parent' => $entityTable],
                new \Zend_Db_Expr("parent.{$linkField} = main.{$parentFieldName}"),
                ['parent_id' => $entityIdField]
            )
            ->joinInner(
                ['child' => $entityTable],
                new \Zend_Db_Expr("child.{$entityIdField} = main.{$childFieldName}"),
                ['child_id' => $entityIdField, 'sku' => 'sku']
            )
            ->where("parent.{$entityIdField} in (?)", $parentIds);

        if ($relation->getWhere() !== null) {
            $select->where($relation->getWhere());
        }

        $configurationTable = $this->getTable('catalog_product_super_attribute');
        $configurableAttrExpr = "GROUP_CONCAT(DISTINCT super_table.attribute_id SEPARATOR ',')";

        $select->joinLeft(
            ["super_table" => $configurationTable],
            "super_table.product_id = main.{$parentFieldName}",
            ["configurable_attributes" => new \Zend_Db_Expr($configurableAttrExpr)]
        );

        $select->group(["main.{$parentFieldName}", "main.{$childFieldName}"]);

        return $this->addWebsiteFilter($select, "main", $childFieldName, $storeId);
    }

    /**
     * Add website clauses to products selected.
     *
     * @param \Magento\Framework\DB\Select $select
     * @param $productTableName
     * @param $productFieldName
     * @param $storeId
     * @return \Magento\Framework\DB\Select
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function addWebsiteFilter(
        \Magento\Framework\DB\Select $select,
        $productTableName,
        $productFieldName,
        $storeId
    ) {
        $websiteId = $this->getStore($storeId)->getWebsiteId();
        $indexTable = $this->getTable('catalog_product_website');

        $visibilityJoinCond = $this->getConnection()->quoteInto(
            "websites.product_id = $productTableName.$productFieldName AND websites.website_id = ?",
            $websiteId
        );

        $select->useStraightJoin(true)->join(['websites' => $indexTable], $visibilityJoinCond, []);

        return $select;
    }
}