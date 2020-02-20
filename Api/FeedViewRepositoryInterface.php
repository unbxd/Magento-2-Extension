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
 * Interface FeedViewRepositoryInterface
 * @package Unbxd\ProductFeed\Api
 */
interface FeedViewRepositoryInterface
{
    /**
     * Save feed view.
     *
     * @param \Unbxd\ProductFeed\Api\Data\FeedViewInterface $feedView
     * @return \Unbxd\ProductFeed\Api\Data\FeedViewInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(Data\FeedViewInterface $feedView);

    /**
     * Retrieve feed view.
     *
     * @param int $feedViewId
     * @return \Unbxd\ProductFeed\Api\Data\FeedViewInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($feedViewId);

    /**
     * Retrieve feed view's matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Unbxd\ProductFeed\Api\Data\FeedViewSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * Delete feed view.
     *
     * @param \Unbxd\ProductFeed\Api\Data\FeedViewInterface $feedView
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(Data\FeedViewInterface $feedView);

    /**
     * Delete feed view by ID.
     *
     * @param int $feedViewId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($feedViewId);
}