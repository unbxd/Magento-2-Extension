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
use Unbxd\ProductFeed\Api\Data\FeedViewInterface;
use Unbxd\ProductFeed\Model\ResourceModel\FeedView as FeedViewResourceModel;

/**
 * Feed view model
 *
 * Class FeedView
 * @package Unbxd\ProductFeed\Model
 */
class FeedView extends AbstractModel implements FeedViewInterface
{
    /**#@+
     * Feed view status codes
     */
    const STATUS_RUNNING = 1;
    const STATUS_COMPLETE = 2;
    const STATUS_ERROR = 3;
    const STATUS_INDEXING = 4;
    /**#@-*/

    /**#@+
     * Feed view status labels
     */
    const STATUS_RUNNING_LABEL = 'Running';
    const STATUS_COMPLETE_LABEL = 'Complete';
    const STATUS_ERROR_LABEL = 'Error';
    const STATUS_INDEXING_LABEL = 'Indexing';
    /**#@-*/

    /**#@+
     * Label for full catalog reindex
     */
    const FEED_FULL_LABEL = 'Full Catalog Products';
    /**#@-*/

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(FeedViewResourceModel::class);
    }

    /**
     * Prepare feed view statuses.
     *
     * @return array
     */
    public function getAvailableStatuses()
    {
        return [
            self::STATUS_RUNNING => __(self::STATUS_RUNNING_LABEL),
            self::STATUS_COMPLETE => __(self::STATUS_COMPLETE_LABEL),
            self::STATUS_ERROR => __(self::STATUS_ERROR_LABEL),
            self::STATUS_INDEXING => __(self::STATUS_INDEXING_LABEL)
        ];
    }

    /**
     * Retrieve feed view id
     *
     * @return int
     */
    public function getId()
    {
        return $this->getData(self::FEED_ID);
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
     * Retrieve operation types
     *
     * @return string
     */
    public function getOperationTypes()
    {
        return $this->getData(self::OPERATION_TYPES);
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
     * Retrieve upload ID
     *
     * @return mixed|string|null
     */
    public function getUploadId()
    {
        return $this->getData(self::UPLOAD_ID);
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
     * Retrieve reindex job ID
     *
     * @return int
     */
    public function getReindexJobId()
    {
        return $this->getData(self::REINDEX_JOB_ID);
    }

    /**
     * Set ID
     *
     * @param int $id
     * @return FeedViewInterface
     */
    public function setId($id)
    {
        return $this->setData(self::FEED_ID, $id);
    }

    /**
     * Set store ID
     *
     * @param int $storeId
     * @return FeedViewInterface
     */
    public function setStoreId($storeId)
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    /**
     * Set created at
     *
     * @param string $createdAt
     * @return FeedViewInterface
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * Set finished at
     *
     * @param string $finishedAt
     * @return FeedViewInterface
     */
    public function setFinishedAt($finishedAt)
    {
        return $this->setData(self::FINISHED_AT, $finishedAt);
    }

    /**
     * Set is active
     *
     * @param bool|int $isActive
     * @return FeedViewInterface
     */
    public function setIsActive($isActive)
    {
        return $this->setData(self::IS_ACTIVE, $isActive);
    }

    /**
     * Set execution time
     *
     * @param string $executionTime
     * @return FeedViewInterface
     */
    public function setExecutionTime($executionTime)
    {
        return $this->setData(self::EXECUTION_TIME, $executionTime);
    }

    /**
     * Set data for affected entities
     *
     * @param string $data
     * @return FeedViewInterface
     */
    public function setAffectedEntities($data)
    {
        return $this->setData(self::AFFECTED_ENTITIES, $data);
    }

    /**
     * Set number of entities
     *
     * @param int $numberOfEntities
     * @return FeedViewInterface
     */
    public function setNumberOfEntities($numberOfEntities)
    {
        return $this->setData(self::NUMBER_OF_ENTITIES, $numberOfEntities);
    }

    /**
     * Set operation types
     *
     * @param string $types
     * @return FeedViewInterface
     */
    public function setOperationTypes($types)
    {
        return $this->setData(self::OPERATION_TYPES, $types);
    }

    /**
     * Set status
     *
     * @param int $status
     * @return FeedViewInterface
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * Set additional information
     *
     * @param string $additionalInformation
     * @return FeedViewInterface
     */
    public function setAdditionalInformation($additionalInformation)
    {
        return $this->setData(self::ADDITIONAL_INFORMATION, $additionalInformation);
    }

    /**
     * Set system information
     *
     * @param string $systemInformation
     * @return FeedViewInterface
     */
    public function setSystemInformation($systemInformation)
    {
        return $this->setData(self::SYSTEM_INFORMATION, $systemInformation);
    }

    /**
     * Set upload ID
     *
     * @param $uploadId
     * @return FeedViewInterface
     */
    public function setUploadId($uploadId)
    {
        return $this->setData(self::UPLOAD_ID, $uploadId);
    }

    /**
     * Set the number of attempts
     *
     * @param $value
     * @return FeedViewInterface
     */
    public function setNumberOfAttempts($value)
    {
        return $this->setData(self::NUMBER_OF_ATTEMPTS, $value);
    }

    /**
     * Set reindex job ID
     *
     * @param int $id
     * @return FeedViewInterface
     */
    public function setReindexJobId($id)
    {
        return $this->setData(self::REINDEX_JOB_ID, $id);
    }
}