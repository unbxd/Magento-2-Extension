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
namespace Unbxd\ProductFeed\Api;

/**
 * Interface IndexingQueueRepositoryInterface
 * @package Unbxd\ProductFeed\Api
 */
interface IndexingQueueRepositoryInterface
{
    /**
     * Save queue.
     *
     * @param \Unbxd\ProductFeed\Api\Data\IndexingQueueInterface $queue
     * @return \Unbxd\ProductFeed\Api\Data\IndexingQueueInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(Data\IndexingQueueInterface $queue);

    /**
     * Retrieve queue.
     *
     * @param int $queueId
     * @return \Unbxd\ProductFeed\Api\Data\IndexingQueueInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($queueId);

    /**
     * Retrieve queue's matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Unbxd\ProductFeed\Api\Data\IndexingQueueSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * Delete queue.
     *
     * @param \Unbxd\ProductFeed\Api\Data\IndexingQueueInterface $queue
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(Data\IndexingQueueInterface $queue);

    /**
     * Delete queue by ID.
     *
     * @param int $queueId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($queueId);


    /**
     * Update Queue in bulk which matches the given conditions
     *
     * @param array $conditions
     * @param array $columnData
     * @return bool
     * @throws CouldNotSaveException
     */
    public function updateIndexQueueRecords($conditions,$columnData);

    /**
     * Delete queue records which matches the condition
     *
     * @param array $conditions
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function deleteIndexQueueRecords($conditions);



}