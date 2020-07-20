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
namespace Unbxd\ProductFeed\Model;

use Magento\Framework\Model\AbstractModel;
use Unbxd\ProductFeed\Api\Data\IndexingQueueInterface;
use Unbxd\ProductFeed\Model\ResourceModel\IndexingQueue as IndexingQueueResourceModel;

/**
 * Indexing queue model
 *
 * Class IndexingQueue
 * @package Unbxd\ProductFeed\Model
 */
class IndexingQueue extends AbstractModel implements IndexingQueueInterface
{
    /**#@+
     * Queue's status codes
     */
    const STATUS_PENDING = 1;
    const STATUS_RUNNING = 2;
    const STATUS_COMPLETE = 3;
    const STATUS_ERROR = 4;
    const STATUS_HOLD = 5;
    const STATUS_SKIPPED = 6;
    /**#@-*/

    /**#@+
     * Queue's status labels
     */
    const STATUS_PENDING_LABEL = 'Pending';
    const STATUS_RUNNING_LABEL = 'Running';
    const STATUS_COMPLETE_LABEL = 'Complete';
    const STATUS_ERROR_LABEL = 'Error';
    const STATUS_HOLD_LABEL = 'Hold';
    const STATUS_SKIPPED_LABEL = 'Skipped';
    /**#@-*/

    /**#@+
     * Queue's action types
     */
    const TYPE_REINDEX_ROW = 1; // product update/delete (e.q. - add new attribute, change attribute value)
    const TYPE_REINDEX_LIST = 2; // product mass update
    const TYPE_REINDEX_FULL = 3; // full catalog
    /**#@-*/

    /**#@+
     * Label for full catalog reindex
     */
    const REINDEX_FULL_LABEL = 'Full Catalog Products';
    /**#@-*/

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(IndexingQueueResourceModel::class);
    }

    /**
     * Prepare queue's statuses.
     *
     * @return array
     */
    public function getAvailableStatuses()
    {
        return [
            self::STATUS_PENDING => __(self::STATUS_PENDING_LABEL),
            self::STATUS_RUNNING => __(self::STATUS_RUNNING_LABEL),
            self::STATUS_COMPLETE => __(self::STATUS_COMPLETE_LABEL),
            self::STATUS_ERROR => __(self::STATUS_ERROR_LABEL),
            self::STATUS_HOLD => __(self::STATUS_HOLD_LABEL),
            self::STATUS_SKIPPED => __(self::STATUS_SKIPPED_LABEL)
        ];
    }

    /**
     * Prepare queue's action types.
     *
     * @return array
     */
    public function getAvailableActionTypes()
    {
        return [
            self::TYPE_REINDEX_ROW => __('Row Reindex'),
            self::TYPE_REINDEX_LIST => __('List Reindex'),
            self::TYPE_REINDEX_FULL => __('Full Reindex'),
        ];
    }

    /**
     * Retrieve queue id
     *
     * @return int
     */
    public function getId()
    {
        return $this->getData(self::QUEUE_ID);
    }

    /**
     * Retrieve store id
     *
     * @return int
     */
    public function getStoreId()
    {
        return $this->getData(self::STORE_ID);
    }

    /**
     * Retrieve created at time
     *
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * Retrieve started at time
     *
     * @return string
     */
    public function getStartedAt()
    {
        return $this->getData(self::STARTED_AT);
    }

    /**
     * Retrieve finished at time
     *
     * @return string
     */
    public function getFinishedAt()
    {
        return $this->getData(self::FINISHED_AT);
    }

    /**
     * Is active
     *
     * @return bool
     */
    public function isActive()
    {
        return (bool) $this->getData(self::IS_ACTIVE);
    }

    /**
     * Retrieve execution time
     *
     * @return string
     */
    public function getExecutionTime()
    {
        return $this->getData(self::EXECUTION_TIME);
    }

    /**
     * Retrieve affected entities
     *
     * @return string
     */
    public function getAffectedEntities()
    {
        return $this->getData(self::AFFECTED_ENTITIES);
    }

    /**
     * Retrieve number of entities
     *
     * @return int
     */
    public function getNumberOfEntities()
    {
        return $this->getData(self::NUMBER_OF_ENTITIES);
    }

    /**
     * Retrieve action type
     *
     * @return string
     */
    public function getActionType()
    {
        return $this->getData(self::ACTION_TYPE);
    }

    /**
     * Retrieve status
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * Retrieve additional information
     *
     * @return string
     */
    public function getAdditionalInformation()
    {
        return $this->getData(self::ADDITIONAL_INFORMATION);
    }

    /**
     * Retrieve system information
     *
     * @return string
     */
    public function getSystemInformation()
    {
        return $this->getData(self::SYSTEM_INFORMATION);
    }

    /**
     * Retrieve the number of attempts
     *
     * @return int
     */
    public function getNumberOfAttempts()
    {
        return $this->getData(self::NUMBER_OF_ATTEMPTS);
    }

    /**
     * Retrieve feed view ID
     *
     * @return int
     */
    public function getFeedViewId()
    {
        return $this->getData(self::FEED_VIEW_ID);
    }

    /**
     * Set ID
     *
     * @param int $id
     * @return IndexingQueueInterface
     */
    public function setId($id)
    {
        return $this->setData(self::QUEUE_ID, $id);
    }

    /**
     * Set store ID
     *
     * @param int $storeId
     * @return IndexingQueueInterface
     */
    public function setStoreId($storeId)
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    /**
     * Set created at
     *
     * @param string $createdAt
     * @return IndexingQueueInterface
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * Set started at
     *
     * @param string $startedAt
     * @return IndexingQueueInterface
     */
    public function setStartedAt($startedAt)
    {
        return $this->setData(self::FINISHED_AT, $startedAt);
    }

    /**
     * Set finished at
     *
     * @param string $finishedAt
     * @return IndexingQueueInterface
     */
    public function setFinishedAt($finishedAt)
    {
        return $this->setData(self::FINISHED_AT, $finishedAt);
    }

    /**
     * Set is active
     *
     * @param bool|int $isActive
     * @return IndexingQueueInterface
     */
    public function setIsActive($isActive)
    {
        return $this->setData(self::IS_ACTIVE, $isActive);
    }

    /**
     * Set execution time
     *
     * @param string $executionTime
     * @return IndexingQueueInterface
     */
    public function setExecutionTime($executionTime)
    {
        return $this->setData(self::EXECUTION_TIME, $executionTime);
    }

    /**
     * Set data for affected entities
     *
     * @param string $data
     * @return IndexingQueueInterface
     */
    public function setAffectedEntities($data)
    {
        return $this->setData(self::AFFECTED_ENTITIES, $data);
    }

    /**
     * Set number of entities
     *
     * @param int $numberOfEntities
     * @return IndexingQueueInterface
     */
    public function setNumberOfEntities($numberOfEntities)
    {
        return $this->setData(self::NUMBER_OF_ENTITIES, $numberOfEntities);
    }

    /**
     * Set action type
     *
     * @param string $actionType
     * @return IndexingQueueInterface
     */
    public function setActionType($actionType)
    {
        return $this->setData(self::ACTION_TYPE, $actionType);
    }

    /**
     * Set status
     *
     * @param int $status
     * @return IndexingQueueInterface
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * Set additional information
     *
     * @param string $additionalInformation
     * @return IndexingQueueInterface
     */
    public function setAdditionalInformation($additionalInformation)
    {
        return $this->setData(self::ADDITIONAL_INFORMATION, $additionalInformation);
    }

    /**
     * Set system information
     *
     * @param string $systemInformation
     * @return IndexingQueueInterface
     */
    public function setSystemInformation($systemInformation)
    {
        return $this->setData(self::SYSTEM_INFORMATION, $systemInformation);
    }

    /**
     * Set the number of attempts
     *
     * @param int $value
     * @return IndexingQueueInterface
     */
    public function setNumberOfAttempts($value)
    {
        return $this->setData(self::NUMBER_OF_ATTEMPTS, $value);
    }

    /**
     * Set feed view ID
     *
     * @param int $id
     * @return IndexingQueueInterface
     */
    public function setFeedViewId($id)
    {
        return $this->setData(self::FEED_VIEW_ID, $id);
    }
}