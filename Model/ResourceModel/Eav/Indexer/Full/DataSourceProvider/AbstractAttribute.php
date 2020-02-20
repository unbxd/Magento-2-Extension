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
namespace Unbxd\ProductFeed\Model\ResourceModel\Eav\Indexer\Full\DataSourceProvider;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Store\Model\StoreManagerInterface;
use Unbxd\ProductFeed\Model\ResourceModel\Eav\Indexer\Indexer;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection as AttributeCollection;
use Magento\Framework\Indexer\Table\StrategyInterface;

/**
 * Abstract data source to retrieve attributes of EAV entities.
 *
 * Class AbstractAttribute
 * @package Unbxd\ProductFeed\Model\ResourceModel\Eav\Indexer\Full\DataSource
 */
class AbstractAttribute extends Indexer
{
    /**
     * @var null|string
     */
    private $entityTypeId = null;

    /**
     * AbstractAttribute constructor.
     * @param ResourceConnection $resource
     * @param StrategyInterface $tableStrategy
     * @param StoreManagerInterface $storeManager
     * @param MetadataPool $metadataPool
     * @param null $entityType
     */
    public function __construct(
        ResourceConnection $resource,
        StrategyInterface $tableStrategy,
        StoreManagerInterface $storeManager,
        MetadataPool $metadataPool,
        $entityType = null
    ) {
        $this->entityTypeId = $entityType;
        parent::__construct(
            $resource,
            $tableStrategy,
            $storeManager,
            $metadataPool
        );
    }

    /**
     * Load default attribute codes for product entity.
     *
     * @return array
     */
    public function getDefaultAttributeFields()
    {
        return array_flip(
            array_keys($this->connection->describeTable($this->getTable('catalog_product_entity')))
        );
    }

    /**
     * Load attribute data for a list of entity ids.
     *
     * @param $storeId
     * @param array $entityIds
     * @param $tableName
     * @param array $attributeIds
     * @return array
     * @throws \Exception
     */
    public function getAttributesRawData($storeId, array $entityIds, $tableName, array $attributeIds)
    {
        $select = $this->connection->select();

        // the field modelizing the link between entity table and attribute values table, either row_id or entity_id.
        $linkField = $this->getEntityMetaData($this->getEntityTypeId())->getLinkField();

        // the legacy entity_id field.
        $entityIdField = $this->getEntityMetaData($this->getEntityTypeId())->getIdentifierField();

        $joinStoreValuesConditionClauses = [
            "t_default.$linkField = t_store.$linkField",
            't_default.attribute_id = t_store.attribute_id',
            't_store.store_id = ?',
        ];

        $joinStoreValuesCondition = $this->connection->quoteInto(
            implode(' AND ', $joinStoreValuesConditionClauses),
            $storeId
        );

        $select->from(
            ['entity' => $this->getEntityMetaData($this->getEntityTypeId())->getEntityTable()],
            [$entityIdField]
            )
            ->joinInner(
                ['t_default' => $tableName],
                new \Zend_Db_Expr("entity.{$linkField} = t_default.{$linkField}"),
                ['attribute_id']
            )
            ->joinLeft(['t_store' => $tableName], $joinStoreValuesCondition, [])
            ->where('t_default.store_id = ?', 0)
            ->where('t_default.attribute_id IN (?)', $attributeIds)
            ->where("entity.{$entityIdField} IN (?)", $entityIds)
            ->columns(['value' => new \Zend_Db_Expr('COALESCE(t_store.value, t_default.value)')]);

        return $this->connection->fetchAll($select);
    }

    /**
     * Get entity type Id.
     *
     * @return string
     */
    protected function getEntityTypeId()
    {
        return $this->entityTypeId;
    }
}