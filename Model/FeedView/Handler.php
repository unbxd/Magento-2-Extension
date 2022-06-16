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
namespace Unbxd\ProductFeed\Model\FeedView;

use Unbxd\ProductFeed\Logger\LoggerInterface;
use Unbxd\ProductFeed\Logger\OptionsListConstants;
use Unbxd\ProductFeed\Api\Data\FeedViewInterface;
use Unbxd\ProductFeed\Model\Feed\Config as FeedConfig;
use Unbxd\ProductFeed\Model\FeedView;
use Unbxd\ProductFeed\Model\FeedViewFactory;
use Unbxd\ProductFeed\Api\FeedViewRepositoryInterface;
use Unbxd\ProductFeed\Model\ResourceModel\FeedView as FeedViewResourceModel;
use Unbxd\ProductFeed\Helper\ProductHelper;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\DataObject;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Feed view handler
 *
 * Class Handler
 * @package Unbxd\ProductFeed\Model\IndexingQueue
 */
class Handler extends \Magento\Framework\DataObject
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var FeedViewFactory
     */
    private $feedViewFactory;

    /**
     * @var FeedViewRepositoryInterface
     */
    private $feedViewRepository;

    /**
     * @var FeedViewResourceModel
     */
    private $feedViewResourceModel;

    /**
     * @var ProductHelper
     */
    private $productHelper;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Array of callbacks subscribed to feed view action
     *
     * @var array
     */
    public static $callbacks = [];

    /**
     * Handler constructor.
     * @param LoggerInterface $logger
     * @param FeedViewFactory $feedViewFactory
     * @param FeedViewRepositoryInterface $feedViewRepository
     * @param FeedViewResourceModel $feedViewResourceModel
     * @param ProductHelper $productHelper
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        LoggerInterface $logger,
        FeedViewFactory $feedViewFactory,
        FeedViewRepositoryInterface $feedViewRepository,
        FeedViewResourceModel $feedViewResourceModel,
        ProductHelper $productHelper,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->logger = $logger->create(OptionsListConstants::LOGGER_TYPE_FEED);
        $this->feedViewFactory = $feedViewFactory;
        $this->feedViewRepository = $feedViewRepository;
        $this->feedViewResourceModel = $feedViewResourceModel;
        $this->productHelper = $productHelper;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->storeManager = $storeManager;
        parent::__construct($data);
    }

    /**
     * @return array
     */
    private function getCallbacks()
    {
        return self::$callbacks;
    }

    /**
     * Initialize feed view
     *
     * @param $id
     * @return FeedViewInterface|null
     * @throws LocalizedException
     */
    public function init($id)
    {
        $model = null;
        try {
            /** @var \Unbxd\ProductFeed\Api\Data\FeedViewInterface $model */
            $model = $this->feedViewRepository->getById($id);
            $this->logger->info('Initialize feed view record with #' . $id);
        } catch (NoSuchEntityException $e) {
            $this->logger->error(sprintf('Feed view record with #%s is no longer exist.', $id));
            return $model;
        }

        return $model;
    }

    /**
     * Save feed view
     *
     * @param $model
     * @return FeedViewInterface
     */
    public function save($model)
    {
        try {
            /** @var \Unbxd\ProductFeed\Api\Data\FeedViewInterface $model */
            $model = $this->feedViewRepository->save($model);
            $this->logger->info('Successfully saved feed view record with #' . $model->getId());
        } catch (LocalizedException $e) {
            $this->logger->error('Can\'t save feed view record. LocalizedException error: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->logger->error('Can\'t save feed view record. Exception error: ' . $e->getMessage());
        }

        return $model;
    }

    /**
     * Prepare feed view record to save
     *
     * @param $entityIds
     * @param $operationTypes
     * @param string $storeId
     * @param array $arguments
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function add($entityIds, $operationTypes, $storeId = '', $arguments = [])
    {
        if (empty($entityIds)) {
            $affectedEntities = FeedView::FEED_FULL_LABEL;
            $qty = count($this->productHelper->getAllProductsIds($storeId));
        } else {
            $affectedEntities = (string) $this->convertIdsToString($entityIds);
            $qty = count($entityIds);
        }

        if (!$storeId) {
            $storeId = $this->getStore()->getId();
        }

        $this->logger->info('Prepare data for add to feed view list');

        /** @var FeedView $model */
        $model = $this->feedViewFactory->create();
        $model->setStoreId($storeId)
            ->setStatus(FeedView::STATUS_RUNNING)
            ->setExecutionTime(0)
            ->setAffectedEntities($affectedEntities)
            ->setNumberOfEntities($qty)
            ->setOperationTypes($operationTypes)
            ->setNumberOfAttempts((int) $model->getNumberOfAttempts() + 1);

        if (!empty($arguments)) {
            foreach ($arguments as $key => $value) {
                $model->setData($key, $value);
            }
        }

        $model = $this->save($model);

        return $model->getId();
    }

    /**
     * Update feed view record from related list
     *
     * @param $id
     * @param array $arguments
     * @return $this
     */
    public function update($id, $arguments)
    {
        try {
            /** @var \Unbxd\ProductFeed\Api\Data\FeedViewInterface $model */
            $model = $this->init($id);
            if ($model->getId() && !empty($arguments)) {
                foreach ($arguments as $key => $value) {
                    $model->setData($key, $value);
                }
                $this->save($model);
                $this->logger->info('Updated feed view record with #' . $model->getId());
            }
        } catch (LocalizedException $e) {
            $this->logger->critical($e);
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }

        return $this;
    }

    /**
     * Mass update feed view record(s) from related list
     *
     * @param array $arguments - key: record identifier, value - record data;
     * @return $this
     */
    public function massUpdate($arguments)
    {
        $ids = array_keys($arguments);

        $filter = new DataObject();
        $filter->setData([
            'field' => 'feed_id',
            'value' => $ids,
            'condition_type' => 'in'
        ]);
        $items = $this->getAffectedProducts(true, $filter);
        if (!empty($items)) {
            foreach ($items as $item) {
                /** \Unbxd\ProductFeed\Api\Data\FeedViewInterface $item */
                // @TODO - implement
            }
        }

        return $this;
    }

    /**
     * Retrieve products affected by feed action
     *
     * @param bool $useFilter
     * @param DataObject|null $filter
     * @return array|FeedViewInterface[]
     */
    public function getAffectedProducts($useFilter = false, DataObject $filter = null)
    {
        if ($useFilter) {
            if ($filter && (!$filter instanceof DataObject)) {
                throw new \InvalidArgumentException('Given filter does not implement \Magento\Framework\DataObject');
            }

            $this->searchCriteriaBuilder->addFilter(
                $filter->getField(),
                $filter->getValue(),
                $filter->getConditionType()
            );
        }

        /** @var \Magento\Framework\Api\SearchCriteria $searchCriteria */
        $searchCriteria = $this->searchCriteriaBuilder->create();

        $items = [];
        try {
            /** \Unbxd\ProductFeed\Api\Data\FeedViewInterface[] $items */
            $items = $this->feedViewRepository->getList($searchCriteria)->getItems();
        } catch (LocalizedException $e) {
            $this->logger->error(
                'Can\'t retrieve affected products list of feed view record. LocalizedException error: ' . $e->getMessage()
            );
        } catch (\Exception $e) {
            $this->logger->error(
                'Can\'t retrieve affected products list of feed view record. Exception error: ' . $e->getMessage()
            );
        }

        return $items;
    }

    /**
     * Delete feed view records from related list
     *
     * @param $id
     * @return $this
     */
    public function delete($id)
    {
        try {
            /** @var \Unbxd\ProductFeed\Api\Data\FeedViewInterface $model */
            $this->feedViewRepository->deleteById($id);
        } catch (LocalizedException $e) {
            $this->logger->error(
                'Can\'t delete record from feed view. LocalizedException error: ' . $e->getMessage()
            );
        } catch (\Exception $e) {
            $this->logger->error(
                'Can\'t delete record from feed view. Exception error: ' . $e->getMessage()
            );
        }

        return $this;
    }

    /**
     * @param array $entitiesIds
     * @return string
     */
    public function convertIdsToString(array $entitiesIds)
    {
        $entitiesIds = array_map(function($id) {
            return sprintf('#%s', trim($id?? ''));
        }, $entitiesIds);

        return implode(', ', $entitiesIds);
    }

    /**
     * @param $string
     * @return array
     */
    public function convertStringToIds($string)
    {
        $entityIds = array_map(function($item) {
            return trim($item?? '', '#');
        }, explode(', ', $string));

        return $entityIds;
    }

    /**
     * @param null $storeId
     * @return \Magento\Store\Api\Data\StoreInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getStore($storeId = null)
    {
        return $this->storeManager->getStore($storeId);
    }
}