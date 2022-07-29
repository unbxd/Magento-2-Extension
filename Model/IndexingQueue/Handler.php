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
namespace Unbxd\ProductFeed\Model\IndexingQueue;

use Unbxd\ProductFeed\Logger\LoggerInterface;
use Unbxd\ProductFeed\Logger\OptionsListConstants;
use Unbxd\ProductFeed\Api\Data\IndexingQueueInterface;
use Unbxd\ProductFeed\Model\IndexingQueue;
use Unbxd\ProductFeed\Model\IndexingQueueFactory;
use Unbxd\ProductFeed\Api\IndexingQueueRepositoryInterface;
use Unbxd\ProductFeed\Model\ResourceModel\IndexingQueue as IndexingQueueResourceModel;
use Unbxd\ProductFeed\Helper\ProductHelper;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\DataObject;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Indexing queue handler
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
     * @var IndexingQueueFactory
     */
    private $indexingQueueFactory;

    /**
     * @var IndexingQueueRepositoryInterface
     */
    private $indexingQueueRepository;

    /**
     * @var IndexingQueueResourceModel
     */
    private $indexingQueueResourceModel;

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
     * Array of additional information subscribed to reindex action
     *
     * @var array
     */
    public static $additionalInformation = [];

    /**
     * Handler constructor.
     * @param LoggerInterface $logger
     * @param IndexingQueueFactory $indexingQueueFactory
     * @param IndexingQueueRepositoryInterface $indexingQueueRepository
     * @param IndexingQueueResourceModel $indexingQueueResourceModel
     * @param ProductHelper $productHelper
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        LoggerInterface $logger,
        IndexingQueueFactory $indexingQueueFactory,
        IndexingQueueRepositoryInterface $indexingQueueRepository,
        IndexingQueueResourceModel $indexingQueueResourceModel,
        ProductHelper $productHelper,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->logger = $logger->create(OptionsListConstants::LOGGER_TYPE_INDEXING);
        $this->indexingQueueFactory = $indexingQueueFactory;
        $this->indexingQueueRepository = $indexingQueueRepository;
        $this->indexingQueueResourceModel = $indexingQueueResourceModel;
        $this->productHelper = $productHelper;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->storeManager = $storeManager;
        parent::__construct($data);
    }

    /**
     * @return array
     */
    private function getAdditionalInformationCallback()
    {
        return self::$additionalInformation;
    }

    /**
     * Initialize reindex action from indexing queue
     *
     * @param $id
     * @return IndexingQueueInterface|null
     * @throws LocalizedException
     */
    public function init($id)
    {
        $model = null;
        try {
            /** @var \Unbxd\ProductFeed\Api\Data\IndexingQueueInterface $model */
            $model = $this->indexingQueueRepository->getById($id);
            $this->logger->info('Initialize queue record with #' . $id);
        } catch (NoSuchEntityException $e) {
            $this->logger->error(sprintf('Queue record with #%s is no longer exist.', $id));
            return $model;
        }

        return $model;
    }

    /**
     * Save reindex action to indexing queue
     *
     * @param $model
     * @return IndexingQueueInterface
     */
    public function save($model)
    {
        try {
            /** @var \Unbxd\ProductFeed\Api\Data\IndexingQueueInterface $model */
            $model = $this->indexingQueueRepository->save($model);
            $this->logger->info('Successfully saved queue record with #' . $model->getId());
        } catch (LocalizedException $e) {
            $this->logger->error('Can\'t save queue record. LocalizedException error: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->logger->error('Can\'t save queue record. Exception error: ' . $e->getMessage());
        }

        return $model;
    }

    /**
     * Prepare reindex action to indexing queue
     *
     * @param $entityIds
     * @param $actionType
     * @param string $storeId
     * @param array $arguments
     * @return $this
     * @throws NoSuchEntityException
     */
    public function add($entityIds, $actionType, $storeId = '', $arguments = [])
    {
        if (empty($entityIds)) {
            $affectedEntities = IndexingQueue::REINDEX_FULL_LABEL;
            $qty = count($this->productHelper->getAllProductsIds($storeId));
        } else {
            // queue for row/list reindex action
            $affectedEntities = (string) $this->convertIdsToString($entityIds);
            $qty = count($entityIds);
            $actionType = IndexingQueue::TYPE_REINDEX_ROW;
            if ($qty > 1) {
                $actionType = IndexingQueue::TYPE_REINDEX_LIST;
            }
        }

        if (!$storeId) {
            $storeId = $this->getStore()->getId();
        }

        $this->logger->info('Prepare data for add to queue list.');

        /** @var IndexingQueue $model */
        $model = $this->indexingQueueFactory->create();
        $model->setStoreId($storeId)
            ->setStatus(IndexingQueue::STATUS_PENDING)
            ->setExecutionTime(0)
            ->setAffectedEntities($affectedEntities)
            ->setNumberOfEntities($qty)
            ->setActionType($actionType);

        $additionalInformation = $this->getAdditionalInformationCallback();
        if (!empty($additionalInformation)) {
            // retrieve only last record from array, in the case of mass action on the product
            $additionalInformation = array_pop($additionalInformation);
            $model->setAdditionalInformation($additionalInformation);
            $this->logger->info($additionalInformation);
        }

        if (!empty($arguments)) {
            foreach ($arguments as $key => $value) {
                $model->setData($key, $value);
            }
        }

        $this->save($model);

        return $this;
    }

    /**
     * Update reindex action from indexing queue
     *
     * @param $id
     * @param array $arguments
     * @return $this
     */
    public function update($id, $arguments)
    {
        try {
            /** @var \Unbxd\ProductFeed\Api\Data\IndexingQueueInterface $model */
            $model = $this->init($id);
            if ($model->getId() && !empty($arguments)) {
                foreach ($arguments as $key => $value) {
                    $model->setData($key, $value);
                }
                $this->save($model);
                $this->logger->info('Updated queue record with #' . $model->getId());
            }
        } catch (LocalizedException $e) {
            $this->logger->error('Can\'t update queue record. LocalizedException error: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->logger->error('Can\'t update queue record. Exception error: ' . $e->getMessage());
        }

        return $this;
    }

    /**
     * Mass update reindex action from indexing queue
     *
     * @param array $arguments - key: record identifier, value - record data;
     * @return $this
     */
    public function massUpdate($arguments)
    {
        $ids = array_keys($arguments);

        $filter = new DataObject();
        $filter->setData([
            'field' => 'queue_id',
            'value' => $ids,
            'condition_type' => 'in'
        ]);
        $items = $this->getAffectedProducts(true, $filter);
        if (!empty($items)) {
            foreach ($items as $item) {
                /** \Unbxd\ProductFeed\Api\Data\IndexingQueueInterface $item */
                // @TODO - implement
            }
        }

        return $this;
    }

    /**
     * Retrieve products affected by reindex action
     *
     * @param bool $useFilter
     * @param DataObject|null $filter
     * @return array|IndexingQueueInterface[]
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
            /** \Unbxd\ProductFeed\Api\Data\IndexingQueueInterface[] $items */
            $items = $this->indexingQueueRepository->getList($searchCriteria)->getItems();
        } catch (LocalizedException $e) {
            $this->logger->error(
                'Can\'t retrieve affected products list of queue record. LocalizedException error: ' . $e->getMessage()
            );
        } catch (\Exception $e) {
            $this->logger->error(
                'Can\'t retrieve affected products list of queue record. Exception error: ' . $e->getMessage()
            );
        }

        return $items;
    }

    /**
     * Delete reindex action from indexing queue
     *
     * @param $id
     * @return $this
     */
    public function delete($id)
    {
        try {
            /** @var \Unbxd\ProductFeed\Api\Data\IndexingQueueInterface $model */
            $this->indexingQueueRepository->deleteById($id);
        } catch (LocalizedException $e) {
            $this->logger->error(
                'Can\'t delete record from queue. LocalizedException error: ' . $e->getMessage()
            );
        } catch (\Exception $e) {
            $this->logger->error(
                'Can\'t delete record from queue. Exception error: ' . $e->getMessage()
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
            return sprintf('#%s', trim($id));
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
            return trim($item, '#');
        }, explode(', ', $string?? ''));

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