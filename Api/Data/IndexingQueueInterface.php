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
 * Interface IndexingQueueInterface
 * @package Unbxd\ProductFeed\Api\Data
 */
interface IndexingQueueInterface
{
    /**#@+
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const QUEUE_ID                  = 'queue_id';
    const STORE_ID                  = 'store_id';
    const CREATED_AT                = 'created_at';
    const STARTED_AT                = 'started_at';
    const FINISHED_AT               = 'finished_at';
    const IS_ACTIVE                 = 'is_active';
    const EXECUTION_TIME            = 'execution_time';
    const AFFECTED_ENTITIES         = 'affected_entities';
    const NUMBER_OF_ENTITIES        = 'number_of_entities';
    const ACTION_TYPE               = 'action_type';
    const STATUS                    = 'status';
    const ADDITIONAL_INFORMATION    = 'additional_information';
    const SYSTEM_INFORMATION        = 'system_information';
    const NUMBER_OF_ATTEMPTS        = 'number_of_attempts';
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
     * Get started at time
     *
     * @return string|null
     */
    public function getStartedAt();

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
     * Get action type
     *
     * @return string|null
     */
    public function getActionType();

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
     * Get the number of attempts
     *
     * @return int
     */
    public function getNumberOfAttempts();

    /**
     * Set ID
     *
     * @param int $id
     * @return IndexingQueueInterface
     */
    public function setId($id);

    /**
     * Set store ID
     *
     * @param int $storeId
     * @return IndexingQueueInterface
     */
    public function setStoreId($storeId);

    /**
     * Set created at time
     *
     * @param string $createdAt
     * @return IndexingQueueInterface
     */
    public function setCreatedAt($createdAt);

    /**
     * Set started at time
     *
     * @param string $startedAt
     * @return IndexingQueueInterface
     */
    public function setStartedAt($startedAt);

    /**
     * Set finished at time
     *
     * @param string $finishedAt
     * @return IndexingQueueInterface
     */
    public function setFinishedAt($finishedAt);

    /**
     * Set is active
     *
     * @param bool|int $isActive
     * @return IndexingQueueInterface
     */
    public function setIsActive($isActive);

    /**
     * Set execution time
     *
     * @param string $executionTime
     * @return IndexingQueueInterface
     */
    public function setExecutionTime($executionTime);

    /**
     * Set affected entities
     *
     * @param string $data
     * @return IndexingQueueInterface
     */
    public function setAffectedEntities($data);

    /**
     * Set number of entities
     *
     * @param int $numberOfEntities
     * @return IndexingQueueInterface
     */
    public function setNumberOfEntities($numberOfEntities);

    /**
     * Set action type
     *
     * @param int $actionType
     * @return IndexingQueueInterface
     */
    public function setActionType($actionType);

    /**
     * Set status
     *
     * @param int $status
     * @return IndexingQueueInterface
     */
    public function setStatus($status);

    /**
     * Set additional information
     *
     * @param string $additionalInformation
     * @return IndexingQueueInterface
     */
    public function setAdditionalInformation($additionalInformation);

    /**
     * Set system information
     *
     * @param string $systemInformation
     * @return IndexingQueueInterface
     */
    public function setSystemInformation($systemInformation);

    /**
     * Set the number of attempts
     *
     * @param $value
     * @return int
     */
    public function setNumberOfAttempts($value);
}