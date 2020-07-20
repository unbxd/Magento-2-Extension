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
namespace Unbxd\ProductFeed\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\VersionControl\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\RelationComposite;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DB\Select;
use Unbxd\ProductFeed\Api\Data\IndexingQueueInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Indexing queue resource model
 *
 * Class IndexingQueue
 * @package Unbxd\ProductFeed\Model\ResourceModel
 */
class IndexingQueue extends AbstractDb
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var MetadataPool
     */
    protected $metadataPool;

    /**
     * Queue constructor.
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param Snapshot $entitySnapshot
     * @param RelationComposite $entityRelationComposite
     * @param EntityManager $entityManager
     * @param MetadataPool $metadataPool
     * @param StoreManagerInterface $storeManager
     * @param null $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        Snapshot $entitySnapshot,
        RelationComposite $entityRelationComposite,
        EntityManager $entityManager,
        MetadataPool $metadataPool,
        StoreManagerInterface $storeManager,
        $connectionName = null
    ) {
        $this->entityManager = $entityManager;
        $this->metadataPool = $metadataPool;
        $this->storeManager = $storeManager;
        parent::__construct(
            $context,
            $entitySnapshot,
            $entityRelationComposite,
            $connectionName
        );
    }

    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('unbxd_productfeed_indexing_queue', 'queue_id');
    }

    /**
     * @inheritDoc
     */
    public function getConnection()
    {
        return $this->metadataPool->getMetadata(IndexingQueueInterface::class)->getEntityConnection();
    }

    /**
     * Perform actions before object delete
     *
     * @param AbstractModel $model
     * @return AbstractDb
     */
    protected function _beforeDelete(AbstractModel $model)
    {
        return parent::_beforeDelete($model);
    }

    /**
     * Perform actions after object delete
     *
     * @param AbstractModel $model
     * @return AbstractDb
     */
    protected function _afterDelete(AbstractModel $model)
    {
        return parent::_afterDelete($model);
    }

    /**
     * Perform actions before object save
     *
     * @param AbstractModel $model
     * @return AbstractDb
     */
    protected function _beforeSave(AbstractModel $model)
    {
        return parent::_beforeSave($model);
    }

    /**
     * Perform actions after object save
     *
     * @param AbstractModel $model
     * @return AbstractDb
     */
    protected function _afterSave(AbstractModel $model)
    {
        return parent::_afterSave($model);
    }

    /**
     * @param AbstractModel $object
     * @param $value
     * @param null $field
     * @return bool|int|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getQueueId(AbstractModel $object, $value, $field = null)
    {
        $entityMetadata = $this->metadataPool->getMetadata(IndexingQueueInterface::class);
        if (!is_numeric($value) && $field === null) {
            $field = 'queue_id';
        } elseif (!$field) {
            $field = $entityMetadata->getIdentifierField();
        }
        $entityId = $value;
        if ($field != $entityMetadata->getIdentifierField() || $object->getStoreId()) {
            $select = $this->_getLoadSelect($field, $value, $object);
            $select->reset(Select::COLUMNS)
                ->columns($this->getMainTable() . '.' . $entityMetadata->getIdentifierField())
                ->limit(1);
            $result = $this->getConnection()->fetchCol($select);
            $entityId = count($result) ? $result[0] : false;
        }

        return $entityId;
    }

    /**
     * Load an object
     *
     * @param AbstractModel $object
     * @param mixed $value
     * @param null $field
     * @return $this|AbstractDb
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function load(AbstractModel $object, $value, $field = null)
    {
        $entityId = $this->getQueueId($object, $value, $field);
        if ($entityId) {
            $this->entityManager->load($object, $entityId);
        }
        return $this;
    }

    /**
     * @param AbstractModel $object
     * @return $this|AbstractDb
     * @throws \Exception
     */
    public function save(AbstractModel $object)
    {
        $this->entityManager->save($object);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function delete(AbstractModel $object)
    {
        $this->entityManager->delete($object);
        return $this;
    }


    public function updateIndexQueueRecords($conditions,$columnData){
        return $this->getConnection()->update(
            $this->getTable('unbxd_productfeed_indexing_queue'), 
            $columnData, 
            $conditions
        );
    }


    public function deleteIndexQueueRecords($conditions){
        return $this->getConnection()->delete(
            $this->getTable('unbxd_productfeed_indexing_queue'), 
            $conditions
        );
    }
}