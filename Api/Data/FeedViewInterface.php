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
namespace Unbxd\ProductFeed\Api\Data;

/**
 * Interface FeedViewInterface
 * @package Unbxd\ProductFeed\Api\Data
 */
interface FeedViewInterface
{
    /**#@+
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const FEED_ID                   = 'feed_id';
    const STORE_ID                  = 'store_id';
    const CREATED_AT                = 'created_at';
    const FINISHED_AT               = 'finished_at';
    const IS_ACTIVE                 = 'is_active';
    const EXECUTION_TIME            = 'execution_time';
    const NUMBER_OF_ENTITIES        = 'number_of_entities';
    const AFFECTED_ENTITIES         = 'affected_entities';
    const OPERATION_TYPES           = 'operation_types';
    const STATUS                    = 'status';
    const ADDITIONAL_INFORMATION    = 'additional_information';
    const SYSTEM_INFORMATION        = 'system_information';
    const UPLOAD_ID                 = 'upload_id';
    const NUMBER_OF_ATTEMPTS        = 'number_of_attempts';
    const REINDEX_JOB_ID            = 'reindex_job_id';
    /**#@-*/

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId();

    /**
     * Get store ID
     *
     * @return int|null
     */
    public function getStoreId();

    /**
     * Get created at time
     *
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Get finished at time
     *
     * @return string|null
     */
    public function getFinishedAt();

    /**
     * Is active
     *
     * @return bool|null
     */
    public function isActive();

    /**
     * Get execution time
     *
     * @return string|null
     */
    public function getExecutionTime();

    /**
     * Get affected entities
     *
     * @return string|null
     */
    public function getAffectedEntities();

    /**
     * Get number of entities
     *
     * @return int|null
     */
    public function getNumberOfEntities();

    /**
     * Get operation types
     *
     * @return string|null
     */
    public function getOperationTypes();

    /**
     * Get status
     *
     * @return string|null
     */
    public function getStatus();

    /**
     * Get additional information
     *
     * @return string|null
     */
    public function getAdditionalInformation();

    /**
     * Get system information
     *
     * @return string|null
     */
    public function getSystemInformation();

    /**
     * Get upload ID
     *
     * @return string|null
     */
    public function getUploadId();

    /**
     * Get the number of attempts
     *
     * @return int
     */
    public function getNumberOfAttempts();

    /**
     * Get reindex job ID
     *
     * @return int
     */
    public function getReindexJobId();

    /**
     * Set ID
     *
     * @param int $id
     * @return FeedViewInterface
     */
    public function setId($id);

    /**
     * Set store ID
     *
     * @param int $id
     * @return FeedViewInterface
     */
    public function setStoreId($id);

    /**
     * Set created at time
     *
     * @param string $createdAt
     * @return FeedViewInterface
     */
    public function setCreatedAt($createdAt);

    /**
     * Set finished at time
     *
     * @param string $finishedAt
     * @return FeedViewInterface
     */
    public function setFinishedAt($finishedAt);

    /**
     * Set is active
     *
     * @param bool|int $isActive
     * @return FeedViewInterface
     */
    public function setIsActive($isActive);

    /**
     * Set execution time
     *
     * @param string $executionTime
     * @return FeedViewInterface
     */
    public function setExecutionTime($executionTime);

    /**
     * Set affected entities
     *
     * @param string $affectedEntities
     * @return FeedViewInterface
     */
    public function setAffectedEntities($affectedEntities);

    /**
     * Set number of entities
     *
     * @param int $numberOfEntities
     * @return FeedViewInterface
     */
    public function setNumberOfEntities($numberOfEntities);

    /**
     * Set operation types
     *
     * @param string $operationTypes
     * @return FeedViewInterface
     */
    public function setOperationTypes($operationTypes);

    /**
     * Set status
     *
     * @param string $status
     * @return FeedViewInterface
     */
    public function setStatus($status);

    /**
     * Set additional information
     *
     * @param string $additionalInformation
     * @return FeedViewInterface
     */
    public function setAdditionalInformation($additionalInformation);

    /**
     * Set system information
     *
     * @param string $systemInformation
     * @return FeedViewInterface
     */
    public function setSystemInformation($systemInformation);

    /**
     * Set upload ID
     *
     * @param $uploadId
     * @return FeedViewInterface
     */
    public function setUploadId($uploadId);

    /**
     * Set the number of attempts
     *
     * @param int $value
     * @return FeedViewInterface
     */
    public function setNumberOfAttempts($value);

    /**
     * Set reindex job ID
     *
     * @param int $id
     * @return FeedViewInterface
     */
    public function setReindexJobId($id);
}