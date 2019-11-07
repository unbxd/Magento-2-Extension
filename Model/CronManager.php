<?php
/**
 * Copyright (c) 2019 Unbxd Inc.
 */

/**
 * Init development:
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 */
namespace Unbxd\ProductFeed\Model;

use Magento\Store\Model\Store;
use Unbxd\ProductFeed\Api\Data\IndexingQueueInterface;
use Unbxd\ProductFeed\Api\Data\FeedViewInterface;
use Magento\Cron\Model\ConfigInterface;
use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory;
use Magento\Cron\Model\Schedule;
use Magento\Cron\Model\ScheduleFactory;
use Unbxd\ProductFeed\Model\Indexer\Product\Full\Action\Full as FullReindexAction;
use Unbxd\ProductFeed\Model\ResourceModel\IndexingQueue\CollectionFactory as IndexingQueueCollectionFactory;
use Unbxd\ProductFeed\Model\IndexingQueue;
use Unbxd\ProductFeed\Model\IndexingQueue\Handler as QueueHandler;
use Unbxd\ProductFeed\Model\Feed\Config as FeedConfig;
use Unbxd\ProductFeed\Model\Feed\Manager as FeedManager;
use Unbxd\ProductFeed\Model\FeedView;
use Unbxd\ProductFeed\Model\FeedView\Handler as FeedViewHandler;
use Unbxd\ProductFeed\Model\ResourceModel\FeedView\CollectionFactory as FeedViewCollectionFactory;
use Unbxd\ProductFeed\Model\CacheManager;
use Unbxd\ProductFeed\Model\CacheManagerFactory;
use Unbxd\ProductFeed\Model\Feed\Api\Connector as ApiConnector;
use Unbxd\ProductFeed\Model\Feed\Api\ConnectorFactory;
use Unbxd\ProductFeed\Model\Feed\Api\Response as FeedResponse;
use Unbxd\ProductFeed\Helper\Feed as FeedHelper;
use Unbxd\ProductFeed\Helper\Data as HelperData;
use Magento\Framework\DB\Helper as DBHelper;
use Unbxd\ProductFeed\Logger\LoggerInterface;
use Unbxd\ProductFeed\Logger\OptionsListConstants;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class CronManager
 * @package Unbxd\ProductFeed\Model
 */
class CronManager
{
    const FEED_JOB_CODE_PREFIX = 'unbxd_product_feed';

    const FEED_JOB_CODE_UPLOAD = self::FEED_JOB_CODE_PREFIX . '_upload';

    const FEED_JOB_CODE_CHECK_UPLOADED_STATUS = self::FEED_JOB_CODE_PREFIX . '_check_uploaded_status';

    const DEFAULT_COLLECTION_SIZE = 20;

    const DEFAULT_JOBS_LIMIT_PER_RUN = 5;

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var CollectionFactory
     */
    protected $cronFactory;

    /**
     * @var ScheduleFactory
     */
    protected $scheduleFactory;

    /**
     * @var FullReindexAction
     */
    private $fullReindexAction;

    /**
     * @var FeedManager
     */
    private $feedManager;

    /**
     * @var IndexingQueueCollectionFactory
     */
    protected $indexingQueueCollectionFactory;

    /**
     * @var QueueHandler
     */
    private $queueHandler;

    /**
     * @var FeedViewHandler
     */
    private $feedViewHandler;

    /**
     * @var FeedViewCollectionFactory
     */
    private $feedViewCollectionFactory;

    /**
     * @var CacheManagerFactory
     */
    private $cacheManagerFactory;

    /**
     * @var null|CacheManager
     */
    private $cacheManager = null;

    /**
     * @var ConnectorFactory
     */
    private $connectorFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var FeedHelper
     */
    private $feedHelper;

    /**
     * @var HelperData
     */
    private $helperData;

    /**
     * @var DBHelper
     */
    private $resourceHelper;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Cron\Model\ResourceModel\Schedule\Collection
     */
    private $runningSchedules;

    /**
     * Flat to prevent duplicate cron jobs
     *
     * @var bool
     */
    private $lockProcess = false;

    /**
     * Cron jobs cache
     *
     * @var null
     */
    protected $jobs = null;

    /**
     * Local cache for feed API connector manager
     *
     * @var null
     */
    private $connectorManager = null;

    /**
     * CronManager constructor.
     * @param ConfigInterface $config
     * @param CollectionFactory $cronFactory
     * @param ScheduleFactory $scheduleFactory
     * @param FullReindexAction $fullReindexAction
     * @param FeedManager $feedManager
     * @param IndexingQueueCollectionFactory $indexingQueueCollectionFactory
     * @param QueueHandler $queueHandler
     * @param FeedViewHandler $feedViewHandler
     * @param FeedViewCollectionFactory $feedViewCollectionFactory
     * @param \Unbxd\ProductFeed\Model\CacheManagerFactory $cacheManagerFactory
     * @param ConnectorFactory $connectorFactory
     * @param LoggerInterface $logger
     * @param FeedHelper $feedHelper
     * @param HelperData $helperData
     * @param DBHelper $resourceHelper
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ConfigInterface $config,
        CollectionFactory $cronFactory,
        ScheduleFactory $scheduleFactory,
        FullReindexAction $fullReindexAction,
        FeedManager $feedManager,
        IndexingQueueCollectionFactory $indexingQueueCollectionFactory,
        QueueHandler $queueHandler,
        FeedViewHandler $feedViewHandler,
        FeedViewCollectionFactory $feedViewCollectionFactory,
        CacheManagerFactory $cacheManagerFactory,
        ConnectorFactory $connectorFactory,
        LoggerInterface $logger,
        FeedHelper $feedHelper,
        HelperData $helperData,
        DBHelper $resourceHelper,
        StoreManagerInterface $storeManager
    ) {
        $this->config = $config;
        $this->cronFactory = $cronFactory;
        $this->scheduleFactory = $scheduleFactory;
        $this->fullReindexAction = $fullReindexAction;
        $this->feedManager = $feedManager;
        $this->indexingQueueCollectionFactory = $indexingQueueCollectionFactory;
        $this->queueHandler = $queueHandler;
        $this->feedViewHandler = $feedViewHandler;
        $this->cacheManagerFactory = $cacheManagerFactory;
        $this->feedViewCollectionFactory = $feedViewCollectionFactory;
        $this->connectorFactory = $connectorFactory;
        $this->logger = $logger->create(OptionsListConstants::LOGGER_TYPE_INDEXING);
        $this->feedHelper = $feedHelper;
        $this->helperData = $helperData;
        $this->resourceHelper = $resourceHelper;
        $this->storeManager = $storeManager;
    }

    /**
     * @param \Magento\Cron\Model\ResourceModel\Schedule\Collection $collection
     * @param $timeOffset
     * @param string $size
     * @return \Magento\Cron\Model\ResourceModel\Schedule\Collection
     */
    public function filterCollectionByTimeOffset(
        \Magento\Cron\Model\ResourceModel\Schedule\Collection $collection,
        $timeOffset,
        $size = ''
    ) {
        $time = time();
        $to = date('Y-m-d H:i:s', $time);
        $lastTime = $time - $timeOffset;
        $from = date('Y-m-d H:i:s', $lastTime);
        $collection->addFieldToFilter(
            'created_at',
            ['from' => $from, 'to' => $to]
        )->setOrder('schedule_id')->setPageSize($size ?: self::DEFAULT_COLLECTION_SIZE);

        return $collection;
    }

    /**
     * @param \Magento\Cron\Model\ResourceModel\Schedule\Collection $collection
     * @param $jobCode
     * @return \Magento\Cron\Model\ResourceModel\Schedule\Collection
     */
    public function filterCollectionByJobCode(
        \Magento\Cron\Model\ResourceModel\Schedule\Collection $collection,
        $jobCode
    ) {
        $jobCodeLike = $this->resourceHelper->addLikeEscape(
            $jobCode,
            ['position' => 'any']
        );
        $collection->addFieldToFilter(
            'job_code',
            ['like' => $jobCodeLike]
        );

        return $collection;
    }

    /**
     * @param \Magento\Cron\Model\ResourceModel\Schedule\Collection $collection
     * @param $size
     * @return $this
     */
    private function filterCollection(
        \Magento\Cron\Model\ResourceModel\Schedule\Collection $collection,
        $size
    ) {
        // try to retrieve data from last 24hrs
        $this->filterCollectionByTimeOffset($collection, 86400, $size); // 60*60*24
        // retrieve only jobs affected by unxbd feed process
        $this->filterCollectionByJobCode($collection, self::FEED_JOB_CODE_PREFIX);

        return $this;
    }

    /**
     * @param int $size
     * @return null
     */
    public function getCronJobs($size)
    {
        if ($this->jobs == null) {
            $result = [];
            /** @var \Magento\Cron\Model\ResourceModel\Schedule\Collection $scheduleCollection */
            $scheduleCollection = $this->cronFactory->create();
            $this->filterCollection($scheduleCollection, $size);
            if (count($scheduleCollection) > 0) {
                foreach ($scheduleCollection as $jobRow) {
                    $result[] = [
                        'schedule_id' => isset($jobRow['schedule_id']) ? $jobRow['schedule_id'] : '',
                        'code' => isset($jobRow['job_code']) ? $jobRow['job_code'] : '',
                        'status' => isset($jobRow['status']) ? $jobRow['status'] : '',
                        'created_at' => isset($jobRow['created_at']) ? $jobRow['created_at'] : '',
                        'messages' => isset($jobRow['messages']) ? $jobRow['messages'] : ''
                    ];
                }
                $this->jobs = $result;
            }
        }

        return $this->jobs;
    }

    /**
     * @param $jobCode
     * @param $status
     * @return \Magento\Cron\Model\ResourceModel\Schedule\Collection
     */
    public function getRunningSchedules($jobCode, $status = Schedule::STATUS_RUNNING)
    {
        if (!$this->runningSchedules) {
            /** @var \Magento\Cron\Model\ResourceModel\Schedule\Collection $scheduleCollection */
            $scheduleCollection = $this->cronFactory->create();
            $this->runningSchedules = $scheduleCollection->addFieldToFilter(
                    'status',
                    $status
                )->addFieldToFilter(
                    'job_code',
                    $jobCode
                )->addFieldToFilter(
                    'finished_at',
                    ['null' => true]
                )->load();
        }

        return $this->runningSchedules;
    }

    /**
     * Check if related process is available for execution
     *
     * @return bool
     */
    private function isProcessAvailable()
    {
        // prevent duplicate jobs
        if ($this->lockProcess) {
            $this->logger->info('Lock reindex by another process.');
            return false;
        }

        // check authorization keys
        if (!$this->helperData->isAuthorizationCredentialsSetup()) {
            $this->logger->error('Please check authorization credentials to perform this operation.');
            return false;
        }

        // check if cron is configured
        if (!$this->helperData->isCronConfigured()) {
            $this->logger->error('Cron is not configured. Please configure related cron job to perform this operation.');
            return false;
        }

        return true;
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function uploadFeed()
    {
        if (!$this->isProcessAvailable()) {
            return;
        }

        /** @var \Unbxd\ProductFeed\Model\ResourceModel\IndexingQueue\Collection $jobs */
        $jobs = $this->indexingQueueCollectionFactory->create();
        $jobs->addFieldToFilter(
            IndexingQueueInterface::STATUS,
                ['eq' => IndexingQueue::STATUS_PENDING]
        )->setPageSize(
            self::DEFAULT_JOBS_LIMIT_PER_RUN
        )->setOrder(
            IndexingQueueInterface::QUEUE_ID
        );

        if (!$jobs->getSize()) {
            return;
        }

        $this->logger->info('Run cron job by schedule. Collect tasks.');

        $this->lockProcess = true;

        $indexData = [];
        $isFullReindex = false;
        foreach ($jobs as $job) {
            /** @var \Unbxd\ProductFeed\Model\IndexingQueue $job */
            $jobId = $job->getId();
            if ($job->getActionType() == IndexingQueue::TYPE_REINDEX_FULL) {
                $isFullReindex = true;
            }

            $this->logger->info(sprintf('Prepare job with #%d for reindex.', $jobId));
            // marked job as running
            $this->queueHandler->update($jobId,
                [
                    IndexingQueueInterface::STATUS => IndexingQueue::STATUS_RUNNING,
                    IndexingQueueInterface::STARTED_AT => date('Y-m-d H:i:s')
                ]
            );

            // retrieve entities id, empty array on full reindex
            $jobData = !$isFullReindex ? $this->queueHandler->convertStringToIds($job->getAffectedEntities()) : [];

            $this->logger->info(sprintf('Start reindex for job with #%d', $jobId))->startTimer();

            $isReindexSuccess = false;
            $jobIndexData = [];
            $error = false;
            // @TODO - need to figure out with stores
            $storeId = (!$job->getStoreId() || ($job->getStoreId() == Store::DEFAULT_STORE_ID))
                ? $this->storeManager->getStore()->getId()
                : $job->getStoreId();

            try {
                $jobIndexData = $this->fullReindexAction->rebuildProductStoreIndex($storeId, $jobData);
                $this->logger->info(sprintf('Finished reindex for job with #%s. Stats:', $jobId))->logStats();
                $isReindexSuccess = true;
            } catch (\Exception $e) {
                $error = $e->getMessage();
                $this->logger->error(
                    sprintf('Reindex failed for job with #%d. Error: %s', $jobId, $error)
                );
            }

            $additionalInformation = $job->getAdditionalInformation();
            $successMessage = 'The related data has been rebuilt successfully';
            if ($additionalInformation) {
                $additionalInformation = sprintf(
                    '%s.<br/>%s',
                    $additionalInformation,
                    $successMessage
                    );
            } else {
                $additionalInformation = $successMessage;
            }

            $updateData = [
                IndexingQueueInterface::STATUS => $isReindexSuccess
                    ? IndexingQueue::STATUS_COMPLETE
                    : IndexingQueue::STATUS_ERROR,
                IndexingQueueInterface::FINISHED_AT => date('Y-m-d H:i:s'),
                IndexingQueueInterface::EXECUTION_TIME => $this->logger->getTime(),
                IndexingQueueInterface::ADDITIONAL_INFORMATION => __($additionalInformation),
                IndexingQueueInterface::NUMBER_OF_ATTEMPTS => (int) $job->getNumberOfAttempts() + 1
            ];
            if ($error) {
                $updateData[IndexingQueueInterface::ADDITIONAL_INFORMATION] = $error;
            }

            $this->logger->info(sprintf('Update job record #%d', $jobId));

            $this->queueHandler->update($jobId, $updateData);

            if ($isReindexSuccess && !empty($jobIndexData)) {
                $indexData += $jobIndexData;
            }
        }

        if (empty($indexData)) {
            $this->logger->error('Can\'t execute feed. Empty index data.');
            return;
        }

        $type = $isFullReindex ? FeedConfig::FEED_TYPE_FULL : FeedConfig::FEED_TYPE_INCREMENTAL;
        $this->feedManager->execute($indexData, $type);

        $this->lockProcess = false;
    }

    /**
     * Runs jobs to check uploaded feed status
     *
     * @return void
     */
    public function checkUploadedFeedStatus()
    {
        /** @var \Unbxd\ProductFeed\Model\ResourceModel\FeedView\Collection $jobs */
        $jobs = $this->feedViewCollectionFactory->create();
        $jobs->addFieldToFilter(
            FeedViewInterface::STATUS,
            ['eq' => FeedView::STATUS_INDEXING]
        )->addFieldToFilter(
            FeedViewInterface::UPLOAD_ID,
            ['neq' => null]
        )->setPageSize(
            self::DEFAULT_JOBS_LIMIT_PER_RUN
        )->setOrder(
            FeedViewInterface::FEED_ID
        );

        if (!$jobs->getSize()) {
            return;
        }

        $feedSize = 0;
        $isCacheAffected = false;
        foreach ($jobs as $job) {
            /** @var \Unbxd\ProductFeed\Model\FeedView $job */
            $jobId = $job->getId();
            $uploadId = trim($job->getUploadId());
            $jobType = trim($job->getOperationTypes());
            if (!$jobId || !$uploadId) {
                continue;
            }

            $apiEndpointType = ($jobType == FeedConfig::FEED_TYPE_FULL)
                ? FeedConfig::FEED_TYPE_FULL_UPLOADED_STATUS
                : FeedConfig::FEED_TYPE_INCREMENTAL_UPLOADED_STATUS;

            /** @var ApiConnector $connectorManager */
            $connectorManager = $this->getConnectorManager();
            try {
                $connectorManager->resetHeaders()
                    ->resetParams()
                    ->setExtraParams([FeedViewInterface::UPLOAD_ID => $uploadId])
                    ->execute($apiEndpointType, \Zend_Http_Client::GET);
            } catch (\Exception $e) {
                return;
            }

            /** @var FeedResponse $response */
            $response = $connectorManager->getResponse();
            if ($response instanceof FeedResponse) {
                $responseBodyData = $response->getResponseBodyAsArray();
                if (!empty($responseBodyData)) {
                    $status = array_key_exists(FeedResponse::RESPONSE_FIELD_STATUS, $responseBodyData)
                        ? $responseBodyData[FeedResponse::RESPONSE_FIELD_STATUS]
                        : null;

                    if ($status && ($status != FeedResponse::RESPONSE_FIELD_STATUS_VALUE_INDEXING)) {
                        $status = ($status == FeedResponse::RESPONSE_FIELD_STATUS_VALUE_INDEXED)
                            ? FeedView::STATUS_COMPLETE
                            : FeedView::STATUS_ERROR;
                        $updateData = [
                            FeedViewInterface::STATUS => $status
                        ];
                        if ($response->getIsError()) {
                            $updateData[FeedViewInterface::ADDITIONAL_INFORMATION] = $response->getErrorsAsString();
                        } else if ($response->getIsSuccess()) {
                            $updateData[FeedViewInterface::ADDITIONAL_INFORMATION] =
                                __(FeedConfig::FEED_MESSAGE_BY_RESPONSE_TYPE_COMPLETE);

                            // additional API call to retrieve upload feed size if available
                            $feedSize = $this->retrieveUploadFeedSize($connectorManager, $response);
                            if ($feedSize > 0) {
                                $message = sprintf(FeedConfig::FEED_MESSAGE_UPLOAD_SIZE, $feedSize);
                                $message = sprintf(
                                    '%s<br/>%s',
                                    FeedConfig::FEED_MESSAGE_BY_RESPONSE_TYPE_COMPLETE,
                                    $message
                                );
                                $updateData[FeedViewInterface::ADDITIONAL_INFORMATION] = __($message);
                            }
                        }

                        $this->updateFeedInformation($jobId, $jobType, $updateData, $status, $feedSize);
                        $isCacheAffected = true;
                    }
                }
            }

            $connectorManager->resetExtraParams()
                ->resetResponse();
        }

        if ($isCacheAffected) {
            // in some cases related config info doesn't refreshing on backend frontend
            $this->flushSystemConfigCache();
        }
    }

    /**
     * Retrieve upload feed size after the related data was indexed by Unbxd service
     *
     * @param ApiConnector $connectorManager
     * @param FeedResponse $response
     * @return int
     */
    private function retrieveUploadFeedSize(ApiConnector $connectorManager, FeedResponse $response)
    {
        try {
            $connectorManager->resetHeaders()
                ->resetParams()
                ->execute(FeedConfig::FEED_TYPE_UPLOADED_SIZE, \Zend_Http_Client::GET);
        } catch (\Exception $e) {
            return 0;
        }

        return (int) $response->getUploadedSize();
    }

    /**
     * Update related feed view information and feed configuration data based on API response
     *
     * @param $jobId
     * @param $jobType
     * @param $updateData
     * @param $status
     * @param int $feedSize
     * @return $this
     */
    private function updateFeedInformation($jobId, $jobType, $updateData, $status, $feedSize)
    {
        $this->feedViewHandler->update($jobId, $updateData);
        $this->feedHelper->setLastSynchronizationStatus($status);
        if ($feedSize > 0) {
            $this->feedHelper->setUploadedSize($feedSize);
        }

        $isSuccess = (bool) ($status == FeedView::STATUS_COMPLETE);
        if ($jobType == FeedConfig::FEED_TYPE_FULL) {
            $this->feedHelper->setFullCatalogSynchronizedStatus($isSuccess);
        } else if ($jobType == FeedConfig::FEED_TYPE_INCREMENTAL) {
            $this->feedHelper->setIncrementalProductSynchronizedStatus($isSuccess);
        }

        return $this;
    }

    /**
     * Retrieve all available indexing operation(s) in 'ERROR' state
     * and try to prepare them to the processing (switched to 'PENDING' state)
     *
     * @return $this
     */
    private function reProcessIndexingOperations()
    {
        /** @var \Unbxd\ProductFeed\Model\ResourceModel\IndexingQueue\Collection $jobs */
        $jobs = $this->indexingQueueCollectionFactory->create();
        $jobs->addFieldToFilter(
            IndexingQueueInterface::STATUS,
            ['eq' => IndexingQueue::STATUS_ERROR]
        )->addFieldToFilter(
            IndexingQueueInterface::NUMBER_OF_ATTEMPTS,
            ['lteq' => $this->getMaxNumberOfAttempts()]
        )->setPageSize(
            self::DEFAULT_JOBS_LIMIT_PER_RUN
        )->setOrder(
            IndexingQueueInterface::QUEUE_ID
        );

        if (!$jobs->getSize()) {
            return $this;
        }

        foreach ($jobs as $job) {
            /** @var \Unbxd\ProductFeed\Model\IndexingQueue $job */
            if (!$jobId = $job->getId()) {
                continue;
            }

            $currentNumberOfAttempts = (int) $job->getNumberOfAttempts();
            $updatedData = [
                IndexingQueueInterface::STARTED_AT => '',
                IndexingQueueInterface::FINISHED_AT => '',
                IndexingQueueInterface::EXECUTION_TIME => 0,
                IndexingQueueInterface::STATUS => IndexingQueue::STATUS_PENDING,
                IndexingQueueInterface::ADDITIONAL_INFORMATION => ''
            ];

            if ($currentNumberOfAttempts >= $this->getMaxNumberOfAttempts()) {
                $additionalMessage = sprintf(
                    '%s<br/>Unable to reindex related data. Maximum number of attempts - %s.',
                    $job->getAdditionalInformation(),
                    $currentNumberOfAttempts
                );
                $updatedData = [
                    IndexingQueueInterface::ADDITIONAL_INFORMATION => __($additionalMessage)
                ];
            }

            $this->queueHandler->update($jobId, $updatedData);
        }

        return $this;
    }

    /**
     * Retrieve all available sync operation(s) in 'ERROR' state
     * and try to re-process them
     *
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function reProcessSyncOperations()
    {
        /** @var \Unbxd\ProductFeed\Model\ResourceModel\FeedView\Collection $jobs */
        $jobs = $this->feedViewCollectionFactory->create();
        $jobs->addFieldToFilter(
            FeedViewInterface::STATUS,
            ['eq' => FeedView::STATUS_ERROR]
        )->addFieldToFilter(
            FeedViewInterface::NUMBER_OF_ATTEMPTS,
            ['lteq' => $this->getMaxNumberOfAttempts()]
        )->setPageSize(
            self::DEFAULT_JOBS_LIMIT_PER_RUN
        )->setOrder(
            FeedViewInterface::FEED_ID
        );

        if (!$jobs->getSize()) {
            return $this;
        }

        foreach ($jobs as $job) {
            /** @var \Unbxd\ProductFeed\Model\FeedView $job */
            if (!$jobId = $job->getId()) {
                continue;
            }

            $currentNumberOfAttempts = (int) $job->getNumberOfAttempts();
            if ($currentNumberOfAttempts >= $this->getMaxNumberOfAttempts()) {
                $additionalMessage = sprintf(
                    '%s<br/>Unable to synchronization related data. Maximum number of attempts - %s.',
                    $job->getAdditionalInformation(),
                    $currentNumberOfAttempts
                );
                $this->feedViewHandler->update($jobId,
                    [
                        FeedViewInterface::ADDITIONAL_INFORMATION => __($additionalMessage)
                    ]
                );
            } else {
                $isFullCatalogAffected = (bool) ($job->getOperationTypes() == FeedConfig::FEED_TYPE_FULL);
                $entityIds = $isFullCatalogAffected
                    ? []
                    : $this->queueHandler->convertStringToIds($job->getAffectedEntities());
                $actionType = empty($entityIds) ? IndexingQueue::TYPE_REINDEX_FULL : '';

                // added new job with related data to indexing queue
                $this->queueHandler->add($entityIds, $actionType, $job->getStoreId(),
                    [
                        IndexingQueueInterface::NUMBER_OF_ATTEMPTS => $job->getNumberOfAttempts()
                    ]
                );

                // remove feed view record in 'error' state
                // the new one will be created after the index operation execution, added to queue above
                $this->feedViewHandler->delete($jobId);
            }
        }

        return $this;
    }

    /**
     * Re-process for operations in 'ERROR' state
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function reProcessOperation()
    {
        if (!$this->isProcessAvailable()) {
            return;
        }

        // check indexing operation(s) in 'error' state
        $this->reProcessIndexingOperations();
        // check sync operation(s) in 'error' state
        $this->reProcessSyncOperations();
    }

    /**
     * @return mixed
     */
    private function getMaxNumberOfAttempts()
    {
        return $this->helperData->getMaxNumberOfAttempts();
    }

    /**
     * Clean configuration cache.
     * In some cases related config info doesn't refreshing on backend frontend
     *
     * @return $this
     */
    private function flushSystemConfigCache()
    {
        try {
            $this->getCacheManager()->flushSystemConfigCache();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $this;
    }

    /**
     * Retrieve cache manager instance. Init if needed
     *
     * @return CacheManager|null
     */
    public function getCacheManager()
    {
        if (null === $this->cacheManager) {
            /** @var CacheManager */
            $this->cacheManager = $this->cacheManagerFactory->create();
        }

        return $this->cacheManager;
    }

    /**
     * Retrieve connector manager instance. Init if needed
     *
     * @return ApiConnector|null
     */
    private function getConnectorManager()
    {
        if (null == $this->connectorManager) {
            /** @var ApiConnector */
            $this->connectorManager = $this->connectorFactory->create();
        }

        return $this->connectorManager;
    }
}