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

use Unbxd\ProductFeed\Api\FeedViewRepositoryInterface;
use Unbxd\ProductFeed\Api\Data;
use Unbxd\ProductFeed\Model\ResourceModel\FeedView as ResourceFeedView;
use Unbxd\ProductFeed\Model\FeedViewFactory;
use Unbxd\ProductFeed\Model\ResourceModel\FeedView\CollectionFactory as FeedViewCollectionFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ObjectManager;

/**
 * Class FeedViewRepository
 * @package Unbxd\ProductFeed\Model
 */
class FeedViewRepository implements FeedViewRepositoryInterface
{
    /**
     * @var ResourceFeedView
     */
    protected $resource;

    /**
     * @var FeedViewFactory
     */
    protected $feedViewFactory;

    /**
     * @var FeedViewCollectionFactory
     */
    protected $feedViewCollectionFactory;

    /**
     * @var Data\FeedViewSearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var DataObjectProcessor
     */
    protected $dataObjectProcessor;

    /**
     * @var Data\FeedViewInterface
     */
    protected $dataFeedViewFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * FeedViewRepository constructor.
     * @param ResourceFeedView $resource
     * @param \Unbxd\ProductFeed\Model\FeedViewFactory $feedViewFactory
     * @param Data\FeedViewInterface $dataFeedViewFactory
     * @param FeedViewCollectionFactory $feedViewCollectionFactory
     * @param Data\FeedViewSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ResourceFeedView $resource,
        FeedViewFactory $feedViewFactory,
        Data\FeedViewInterface $dataFeedViewFactory,
        FeedViewCollectionFactory $feedViewCollectionFactory,
        Data\FeedViewSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager
    ) {
        $this->resource = $resource;
        $this->feedViewFactory = $feedViewFactory;
        $this->feedViewCollectionFactory = $feedViewCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataFeedViewFactory = $dataFeedViewFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
        if (interface_exists(CollectionProcessorInterface::class)) {
            $this->collectionProcessor = $this->getCollectionProcessor();
        }
    }

    /**
     * Save feed view data
     *
     * @param Data\FeedViewInterface $feedView
     * @return Data\FeedViewInterface
     * @throws CouldNotSaveException
     */
    public function save(Data\FeedViewInterface $feedView)
    {
        try {
            $this->resource->save($feedView);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }

        return $feedView;
    }

    /**
     * Load feed view data by given identity
     *
     * @param int $feedViewId
     * @return Data\FeedViewInterface|FeedView
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($feedViewId)
    {
        /** @var \Unbxd\ProductFeed\Model\FeedView $feedView */
        $feedView = $this->feedViewFactory->create();
        $this->resource->load($feedView, $feedViewId);
        if (!$feedView->getId()) {
            throw new NoSuchEntityException(__('Feed view with id "%1" does not exist.', $feedViewId));
        }

        return $feedView;
    }

    /**
     * Load feed view collection by given search criteria
     *
     * @param SearchCriteriaInterface $criteria
     * @return Data\FeedViewSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $criteria)
    {
        /** @var \Unbxd\ProductFeed\Model\ResourceModel\FeedView\Collection $collection */
        $collection = $this->feedViewCollectionFactory->create();

        if ($this->collectionProcessor) {
            $searchResults = $this->processCollectionByCollectionProcessor($collection, $criteria);
        } else {
            $searchResults = $this->processCollectionByDefault($collection, $criteria);
        }

        return $searchResults;
    }

    /**
     * @param $collection
     * @param SearchCriteriaInterface $criteria
     * @return mixed
     */
    private function processCollectionByDefault($collection, SearchCriteriaInterface $criteria)
    {
        /** @var Data\FeedViewSearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        foreach ($criteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                if ($filter->getField() === 'store_id') {
                    $collection->addStoreFilter($filter->getValue(), false);
                    continue;
                }
                $condition = $filter->getConditionType() ?: 'eq';
                $collection->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
            }
        }
        $searchResults->setTotalCount($collection->getSize());
        $sortOrders = $criteria->getSortOrders();
        if ($sortOrders) {
            /** @var SortOrder $sortOrder */
            foreach ($sortOrders as $sortOrder) {
                $collection->addOrder(
                    $sortOrder->getField(),
                    ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
                );
            }
        }
        $collection->setCurPage($criteria->getCurrentPage());
        $collection->setPageSize($criteria->getPageSize());
        $items = [];
        /** @var FeedView $feedViewModel */
        foreach ($collection as $feedViewModel) {
            /** @var Data\FeedViewInterface $feedViewData */
            $feedViewData = $this->dataFeedViewFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $feedViewData,
                $feedViewModel->getData(),
                'Unbxd\ProductFeed\Api\Data\FeedViewInterface'
            );
            $items[] = $this->dataObjectProcessor->buildOutputDataArray(
                $feedViewData,
                'Unbxd\ProductFeed\Api\Data\FeedViewInterface'
            );
        }
        $searchResults->setItems($items);

        return $searchResults;
    }

    /**
     * @param $collection
     * @param SearchCriteriaInterface $criteria
     * @return Data\FeedViewSearchResultsInterface
     */
    private function processCollectionByCollectionProcessor($collection, SearchCriteriaInterface $criteria)
    {
        /** @var Data\FeedViewSearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();

        $this->collectionProcessor->process($criteria, $collection);

        $searchResults->setSearchCriteria($criteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    /**
     * Delete feed view
     *
     * @param Data\FeedViewInterface $feedView
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(Data\FeedViewInterface $feedView)
    {
        try {
            $this->resource->delete($feedView);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }

        return true;
    }

    /**
     * Delete feed view by given identity
     *
     * @param int $feedViewId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($feedViewId)
    {
        return $this->delete($this->getById($feedViewId));
    }

     /**
     * Delete feed views by given conditions
     *
     * @param array $conditions
     * @return bool
     * @throws CouldNotDeleteException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteFeedViewRecords($conditions){
        try {
            $this->resource->deleteFeedViewRecords($conditions);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }

        return true;
    }

    /**
     * Retrieve collection processor
     *
     * @return CollectionProcessorInterface
     */
    private function getCollectionProcessor()
    {
        if (!$this->collectionProcessor) {
            $this->collectionProcessor = ObjectManager::getInstance()->get(
                \Unbxd\ProductFeed\Model\Api\SearchCriteria\FeedViewCollectionProcessor::class
            );
        }

        return $this->collectionProcessor;
    }

    
}