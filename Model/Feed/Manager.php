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

namespace Unbxd\ProductFeed\Model\Feed;

use Unbxd\ProductFeed\Helper\Feed;
use Unbxd\ProductFeed\Model\Feed\DataHandlerFactory;
use Unbxd\ProductFeed\Api\Data\FeedViewInterface;
use Unbxd\ProductFeed\Model\FeedView;
use Unbxd\ProductFeed\Helper\Profiler;
use Unbxd\ProductFeed\Model\CacheManager;
use Unbxd\ProductFeed\Model\FeedView\Handler as FeedViewManager;
use Unbxd\ProductFeed\Model\Feed\FileManager as FeedFileManager;
use Unbxd\ProductFeed\Model\Feed\FileManagerFactory;
use Unbxd\ProductFeed\Model\Feed\Api\Connector as ApiConnector;
use Unbxd\ProductFeed\Model\Feed\Api\ConnectorFactory;
use Unbxd\ProductFeed\Model\Feed\Api\Response as FeedResponse;
use Unbxd\ProductFeed\Model\Serializer;
use Magento\Framework\App\ObjectManager;
use Unbxd\ProductFeed\Helper\Feed as FeedHelper;
use Unbxd\ProductFeed\Model\Feed\Config as FeedConfig;
use Unbxd\ProductFeed\Logger\LoggerInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Unbxd\ProductFeed\Helper\Data as ConfigHelper;
use Unbxd\ProductFeed\Model\Feed\JsonStreamWriter;

/**
 * Class Manager
 *
 *  Supported events:
 *   - unbxd_productfeed_send_before
 *   - unbxd_productfeed_send_after
 *   - unbxd_productfeed_uploaded_status_before
 *   - unbxd_productfeed_uploaded_status_after
 *
 * @package Unbxd\ProductFeed\Model\Feed
 */
class Manager
{
    /**
     * @var DataHandlerFactory
     */
    private $dataHandlerFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Profiler
     */
    private $profiler;

    /**
     * @var FeedHelper
     */
    private $feedHelper;

    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @var FeedViewManager
     */
    private $feedViewManager;

    /**
     * @var FileManagerFactory
     */
    private $fileManagerFactory;

    /**
     * @var ConnectorFactory
     */
    private $connectorFactory;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * Prefix of available events for dispatch
     *
     * @var string
     */
    private $eventPrefix = 'unbxd_productfeed';

    /**
     * Local cache for feed file manager
     *
     * @var null
     */
    private $fileManager = null;

    /**
     * Local cache for feed API connector manager
     *
     * @var null
     */
    private $connectorManager = null;

    /**
     * Flag to detect if original file content must be archived or not
     *
     * @var bool
     */
    private $isNeedToArchive = false;

    /**
     * Feed data
     *
     * @var null
     */
    private $feed = null;

    /**
     * Feed type (full or incremental)
     *
     * @var null
     */
    private $type = null;

    /**
     * Flag for detect whether feed locked
     *
     * @var bool
     */
    private $isFeedLock = false;

    /**
     * Feed locked time
     *
     * @var bool
     */
    private $lockedTime = null;

    /**
     * Feed view ID related to current execution
     *
     * @var null
     */
    private $feedViewId = null;

    /**
     * @var int
     */
    private $uploadedFeedSize = 0;

    /**
     * @var string|null
     */
    private $loggerType = null;

    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * 
     *
     * @var JsonStreamWriter
     */
    private $jsonStreamWriter;

    /**
     * Manager constructor.
     * @param \Unbxd\ProductFeed\Model\Feed\DataHandlerFactory $dataHandlerFactory
     * @param Profiler $profiler
     * @param FeedHelper $feedHelper
     * @param CacheManager $cacheManager
     * @param FeedViewManager $feedViewManager
     * @param \Unbxd\ProductFeed\Model\Feed\FileManagerFactory $fileManagerFactory
     * @param ConnectorFactory $connectorFactory
     * @param Serializer $serializer
     * @param EventManager $eventManager
     * @param ConfigHelper $configHelper
     * @param LoggerInterface $logger
     * @param JsonStreamWriter $jsonStreamWriter
     * @param null $loggerType
     * 
     */
    public function __construct(
        DataHandlerFactory $dataHandlerFactory,
        Profiler $profiler,
        FeedHelper $feedHelper,
        CacheManager $cacheManager,
        FeedViewManager $feedViewManager,
        FileManagerFactory $fileManagerFactory,
        ConnectorFactory $connectorFactory,
        Serializer $serializer,
        EventManager $eventManager,
        ConfigHelper $configHelper,
        LoggerInterface $logger,
        JsonStreamWriter $jsonStreamWriter,
        $loggerType = null
    ) {
        $this->dataHandlerFactory = $dataHandlerFactory;
        $this->profiler = $profiler;
        $this->feedHelper = $feedHelper;
        $this->cacheManager = $cacheManager;
        $this->feedViewManager = $feedViewManager;
        $this->fileManagerFactory = $fileManagerFactory;
        $this->connectorFactory = $connectorFactory;
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(Serializer::class);
        $this->eventManager = $eventManager;
        $this->logger = $logger->create($loggerType);
        $this->loggerType = $loggerType;
        $this->configHelper = $configHelper;
        $this->jsonStreamWriter = $jsonStreamWriter;
        $this->setIsNeedToArchive(true);
    }

    /**
     * Start profiling to collect additional system information during execution
     *
     * @return $this
     */
    private function startProfiler()
    {
        $this->profiler->startProfiling();

        return $this;
    }

    /**
     * Stop profiling to collect additional system information during execution
     *
     * @return $this
     */
    private function stopProfiler()
    {
        $this->profiler->stopProfiling();
        $profilerResult = $this->profiler->getProfilingStatAsString();
        $this->logger->debug('Profiler: ' . $profilerResult);

        return $this;
    }

    /**
     * Set feed data
     *
     * @param mixed $feed
     * @return $this
     */
    private function setFeed($feed)
    {
        $this->feed = $feed;

        return $this;
    }

    /**
     * Get feed data
     *
     * @return null
     */
    private function getFeed()
    {
        return $this->feed;
    }

    /**
     * Init execute
     *
     * @param array $index
     * @param null $store
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function initExecute(array $index, $store = null)
    {
        /** @var \Unbxd\ProductFeed\Model\Feed\DataHandler $dataHandler */
        $dataHandler = $this->dataHandlerFactory->create(['loggerType' => $this->loggerType]);
        $dataHandler->initFeed($index, $store);
        $this->setFeed($dataHandler->getFullFeed());
        $dataHandler->reset();

        $index = [];
        return $this;
    }

    private function removeSchema()
    {
        $feedObj = $this->getFeed();
        if (!$feedObj) {
            return;
        }
       
        if (array_key_exists(FeedConfig::FEED_FIELD_KEY, $feedObj) && array_key_exists(FeedConfig::CATALOG_FIELD_KEY, $feedObj[FeedConfig::FEED_FIELD_KEY]) && array_key_exists(FeedConfig::SCHEMA_FIELD_KEY, $feedObj[FeedConfig::FEED_FIELD_KEY][FeedConfig::CATALOG_FIELD_KEY])) {
            $feed = $feedObj["feed"];
            unset($feed["catalog"]["schema"]);
        }

        return $this;
    }

    /**
     * Performing operations related to build and write feed data to a file
     *
     * @param $index
     * @param null $store
     * @return $this
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function executeForDownload($index, $store = null)
    {
        $this->initExecute($index, $store)
            ->serializeAndWriteFeed(
                [
                    'subDir' => FeedFileManager::DEFAULT_SUB_DIR_FOR_DOWNLOAD,
                    'store' => sprintf('%s%s', FeedFileManager::STORE_PARAMETER, $store)
                ]
            );

        return $this;
    }

    public function batchExecute($index, $partCount, $type = FeedConfig::FEED_TYPE_FULL,$store = null)
    {
        if (empty($index)) {
            $this->logger->error('Unable to execute feed. Index data are empty.');
            return false;
        }
        $ids = ($type == FeedConfig::FEED_TYPE_FULL) ? [] : array_keys($index);
        $this->preProcessActions($ids, $type, $store, []);
        $this->startProfiler()
            ->initExecute($index, $store)
            ->removeSchema()
            ->serializeAndWriteFeed(
                [
                    'store' => sprintf('%s%s', FeedFileManager::STORE_PARAMETER, $store),
                    'feedId' => sprintf('_%s_%s', $this->feedViewId, $partCount)
                ],true
            )
            ->stopProfiler();
    }

    /**
     * Performing operations related to synchronization with Unbxd service
     *
     * @param $index
     * @param string $type
     * @param null $store
     * @param array $jobData
     * @return bool|int
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute($index, $type = FeedConfig::FEED_TYPE_FULL, $store = null, $jobData = [])
    {
        if (empty($index)) {
            $this->logger->error('Unable to execute feed. Index data are empty.');
            return false;
        }

        $this->logger->info('START feed execute.')->startTimer();

        $ids = ($type == FeedConfig::FEED_TYPE_FULL) ? [] : array_keys($index);
        $this->preProcessActions($ids, $type, $store, $jobData);
        if ($this->isFeedLock) {
            $this->lockedTime = round(microtime(true) - $this->lockedTime, 2);
            $this->logger->error(
                'Unable to execute feed. Feed lock by another process. Locked time: ' . $this->lockedTime
            );
            return false;
        }

        // caching feed operation type
        $this->type = $type;

        $this->startProfiler()
            ->initExecute($index, $store)
            ->serializeAndWriteFeed(
                [
                    'store' => sprintf('%s%s', FeedFileManager::STORE_PARAMETER, $store),
                    'feedId' => sprintf('_%s', $this->feedViewId)
                ]
            )
            ->sendFeed($store)
            ->postProcessActions()
            ->stopProfiler();

        $this->logger->info('END feed execute.');

        return $this->feedViewId;
    }

    /**
     * @param array $fileParameters
     * @return $this
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function serializeAndWriteFeed($fileParameters = [],$batchUpdate = false)
    {

        if ($this->configHelper->getEnableSerialization()) {
            if (!empty($fileParameters)) {
                $fileManager = $this->getFileManager($fileParameters);
            } else {
                $fileManager = $this->getFileManager();
            }

            // remove related old file(s) if exists
            if ($fileManager->isExist()) {
                $fileManager->deleteAffectedFiles();
            }
            try {
                $fileManager->openStream();
                $feedObj = $this->getFeed();
                $feed = $feedObj["feed"];
                $startMemory = memory_get_usage();
                $this->logger->info("Start Memory Usuage" . $startMemory . "<br>\n");
                $this->jsonStreamWriter->openJsonObject($fileManager)->setAttribute(FeedConfig::FEED_FIELD_KEY, $fileManager)->openJsonObject($fileManager)->setAttribute(FeedConfig::CATALOG_FIELD_KEY, $fileManager)->openJsonObject($fileManager);
                $firstItemInJSON = false;
                if (array_key_exists(FeedConfig::SCHEMA_FIELD_KEY, $feed["catalog"])) {
                    $this->jsonStreamWriter->setAttribute(FeedConfig::SCHEMA_FIELD_KEY, $fileManager)->openArray($fileManager);
                    foreach ($feed["catalog"]["schema"] as $index => $value) {
                        if ($index == 0) {
                            $this->jsonStreamWriter->pushFirstItem($value, $fileManager);
                        } else {
                            $this->jsonStreamWriter->pushItem($value, $fileManager);
                        }
                    }
                    $this->jsonStreamWriter->closeArray($fileManager);
                    $firstItemInJSON = true;
                }

                if (array_key_exists(FeedConfig::OPERATION_TYPE_ADD, $feed["catalog"])) {
                    if ($firstItemInJSON) {
                        $this->jsonStreamWriter->nextItem($fileManager);
                    }
                    $this->jsonStreamWriter->setAttribute(FeedConfig::OPERATION_TYPE_ADD, $fileManager)->openJsonObject($fileManager)->setAttribute(FeedConfig::CATALOG_ITEMS_FIELD_KEY, $fileManager)->openArray($fileManager);
                    foreach ($feed["catalog"]["add"]["items"] as $index => $value) {
                        if ($index == 0) {
                            $this->jsonStreamWriter->pushFirstItem($value, $fileManager);
                        } else {
                            $this->jsonStreamWriter->pushItem($value, $fileManager);
                        }
                    }
                    $this->jsonStreamWriter->closeArray($fileManager)->closeJsonObject($fileManager);
                    $firstItemInJSON = true;
                }
                if (array_key_exists(FeedConfig::OPERATION_TYPE_UPDATE, $feed["catalog"])) {
                    if ($firstItemInJSON) {
                        $this->jsonStreamWriter->nextItem($fileManager);
                    }
                    $this->jsonStreamWriter->setAttribute(FeedConfig::OPERATION_TYPE_UPDATE, $fileManager)->openJsonObject($fileManager)->setAttribute(FeedConfig::CATALOG_ITEMS_FIELD_KEY, $fileManager)->openArray($fileManager);
                    foreach ($feed["catalog"]["update"]["items"] as $index => $value) {
                        if ($index == 0) {
                            $this->jsonStreamWriter->pushFirstItem($value, $fileManager);
                        } else {
                            $this->jsonStreamWriter->pushItem($value, $fileManager);
                        }
                    }
                    $this->jsonStreamWriter->closeArray($fileManager)->closeJsonObject($fileManager);
                    $firstItemInJSON = true;
                }
                if (array_key_exists(FeedConfig::OPERATION_TYPE_DELETE, $feed["catalog"])) {
                    if ($firstItemInJSON) {
                        $this->jsonStreamWriter->nextItem($fileManager);
                    }
                    $this->jsonStreamWriter->setAttribute(FeedConfig::OPERATION_TYPE_DELETE, $fileManager)->openJsonObject($fileManager)->setAttribute(FeedConfig::CATALOG_ITEMS_FIELD_KEY, $fileManager)->openArray($fileManager);
                    foreach ($feed["catalog"]["delete"]["items"] as $index => $value) {
                        if ($index == 0) {
                            $this->jsonStreamWriter->pushFirstItem($value, $fileManager);
                        } else {
                            $this->jsonStreamWriter->pushItem($value, $fileManager);
                        }
                    }
                    $this->jsonStreamWriter->closeArray($fileManager)->closeJsonObject($fileManager);
                    $firstItemInJSON = true;
                }
                $this->jsonStreamWriter->closeJsonObject($fileManager)->closeJsonObject($fileManager)->closeJsonObject($fileManager);
                $this->logger->info("Stream Serialise Feed Current Memory ::" . memory_get_usage() . " with difference " . (memory_get_usage() - $startMemory) . "<br>\n");
            } catch (\Exception $e) {
                $this->logger->critical($e);
                $this->postProcessActions();
                return $this;
            } finally {
                $fileManager->closeStream();
            }
            if ($this->getIsNeedToArchive() && !$batchUpdate) {
                $this->archiveFeedFile();
            }
        } else {
            $this->serializeFeed()->writeFeed($fileParameters);
        }
        return $this;
    }


    /**
     * Serialize formed feed content
     *
     * @return $this
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function serializeFeed()
    {
        $this->logger->info('Serialize feed content.');

        if ($feed = $this->getFeed()) {
            try {
                $startMemory = memory_get_usage();
                $serializedFeed = $this->serializer->serializeToJson($feed);
                $this->logger->info("Serialise Feed Current Memory ::" . memory_get_usage() . " with difference " . (memory_get_usage() - $startMemory));
                $this->setFeed($serializedFeed);
            } catch (\Exception $e) {
                $this->logger->critical($e);
                $this->postProcessActions();
            }
        }

        return $this;
    }

    /**
     * @param array $fileParameters
     * @return $this
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function writeFeed($fileParameters = [])
    {
        $this->logger->info('Write feed content to file.');

        if (is_string($this->getFeed())) {
            /** @var \Unbxd\ProductFeed\Model\Feed\FileManager $fileManager */
            if (!empty($fileParameters)) {
                $fileManager = $this->getFileManager($fileParameters);
            } else {
                $fileManager = $this->getFileManager();
            }

            // remove related old file(s) if exists
            if ($fileManager->isExist()) {
                $fileManager->deleteAffectedFiles();
            }

            try {
                $fileManager->write($this->getFeed());
            } catch (\Exception $e) {
                $this->logger->critical($e);
                $this->postProcessActions();

                return $this;
            }
        }

        // reset local cache for feed content after writing the data to a file
        $this->setFeed(null);

        if ($this->getIsNeedToArchive()) {
            $this->archiveFeedFile();
        }

        return $this;
    }

    /**
     * Pack file to archive.
     *
     * @param $source
     * @param $destination
     * @param null $filename
     * @return mixed
     */
    public function packArchive($source, $destination, $filename = null)
    {
        $zip = new \ZipArchive();
        $zip->open($destination, \ZipArchive::CREATE);
        $zip->addFile($source, $filename);
        $zip->close();

        return $destination;
    }

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function archiveFeedFile()
    {
        $this->logger->info('Archive feed content.');

        /** @var \Unbxd\ProductFeed\Model\Feed\FileManager $fileManager */
        $fileManager = $this->getFileManager();
        // do only if original source file exist
        if ($fileManager->isExist()) {
            $sourceFile = $fileManager->getFileLocation();
            $sourceFileName = $fileManager->getFileName();

            // set flag which indicate that original source file must be archived
            $fileManager->setIsConvertedToArchive(true);
            $archiveDestination = $fileManager->getFileLocation();
            try {
                $archivedFile = $this->packArchive(
                    $sourceFile,
                    $archiveDestination,
                    $sourceFileName
                );
                if (!$archivedFile) {
                    $this->logger->error('Sorry, but the data is invalid or the feed file is not archived.');
                    $this->postProcessActions();
                }
            } catch (\Exception $e) {
                $this->logger->critical($e);
                $this->postProcessActions();
            }
        }

        return $this;
    }

    /**
     * Prepare feed file request parameters which must be send through API
     *
     * @return array
     */
    private function buildFileParameters()
    {
        /** @var \Unbxd\ProductFeed\Model\Feed\FileManager $fileManager */
        $fileManager = $this->getFileManager();
        $params = [];

        if (!$fileManager->isExist()) {
            return $params;
        }

        $filePath = $fileManager->getFileLocation();
        $fileName = $fileManager->getFileName();
        $fileMimeType = $fileManager->getMimeType();

        if (FeedConfig::CURL_FILE_CREATE_POST_PARAM_SUPPORT && function_exists('curl_file_create')) {
            $params['file'] = curl_file_create($filePath, $fileMimeType, $fileName);
        } else {
            $params['file'] = "@$filePath;filename="
                . ($fileName ?: basename($filePath))
                . ($fileMimeType ? ";type=$fileMimeType" : '');
        }

        return $params;
    }

    /**
     * @param ApiConnector $connectorManager
     * @param FeedResponse $response
     * @param null $store
     * @return $this
     */
    private function retrieveUploadedSize(ApiConnector $connectorManager, FeedResponse $response, $store = null)
    {
        $this->logger->info('Retrieve uploaded feed size.');

        try {
            $connectorManager->resetHeaders()
                ->resetParams()
                ->execute(FeedConfig::FEED_TYPE_UPLOADED_SIZE, \Zend_Http_Client::GET, [], [], $store);
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->postProcessActions();

            return $this;
        }

        $recordsQty = $response->getUploadedSize();
        if ($recordsQty > 0) {
            $this->uploadedFeedSize = $recordsQty;
        }

        return $this;
    }

    /**
     * @param ApiConnector $connectorManager
     * @param FeedResponse $response
     * @param null $store
     * @return $this
     */
    private function checkUploadedFeedStatus(ApiConnector $connectorManager, FeedResponse $response, $store = null)
    {
        $this->logger->info('Check uploaded feed status.');

        $this->logger->info('Dispatch event: ' . $this->eventPrefix . '_uploaded_status_before.');
        $this->eventManager->dispatch(
            $this->eventPrefix . '_uploaded_status_before',
            ['response' => $response, 'feed_manager' => $this]
        );

        $apiEndpointType = ($this->type == FeedConfig::FEED_TYPE_FULL)
            ? FeedConfig::FEED_TYPE_FULL_UPLOADED_STATUS
            : FeedConfig::FEED_TYPE_INCREMENTAL_UPLOADED_STATUS;

        try {
            $connectorManager->resetHeaders()
                ->resetParams()
                ->execute($apiEndpointType, \Zend_Http_Client::GET, [], [], $store);
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->postProcessActions();
            return $this;
        }

        $this->logger->info('Dispatch event: ' . $this->eventPrefix . '_uploaded_status_after.');
        $this->eventManager->dispatch(
            $this->eventPrefix . '_uploaded_status_after',
            ['response' => $response, 'feed_manager' => $this]
        );

        return $this;
    }

    /**
     * Send feed data through Unbxd API
     *
     * @param null $store
     * @return $this
     */
    private function sendFeed($store = null)
    {
        $this->logger->info('Send feed to service.');

        $params = $this->buildFileParameters();
        if (empty($params)) {
            $this->logger->error('File parameters for request are empty.');
            return $this;
        }

        $this->logger->info('Dispatch event: ' . $this->eventPrefix . '_send_before.');
        $this->eventManager->dispatch(
            $this->eventPrefix . '_send_before',
            ['file_params' => $params, 'feed_manager' => $this]
        );

        /** @var \Unbxd\ProductFeed\Model\Feed\Api\Connector $connectorManager */
        $connectorManager = $this->getConnectorManager();
        try {
            $connectorManager->execute($this->type, \Zend_Http_Client::POST, [], $params, $store);
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->postProcessActions();
            return $this;
        }

        $this->logger->info('Dispatch event: ' . $this->eventPrefix . '_send_after.');
        $this->eventManager->dispatch(
            $this->eventPrefix . '_send_after',
            ['file_params' => $params, 'feed_manager' => $this]
        );

        /** @var FeedResponse $response */
        $response = $connectorManager->getResponse();
        if ($response instanceof FeedResponse) {
            if (!$response->getIsError()) {
                // additional API calls
                if (FeedConfig::VALIDATE_STATUS_FOR_UPLOADED_FEED) {
                    $this->checkUploadedFeedStatus($connectorManager, $response, $store);
                }
                if (FeedConfig::RETRIEVE_SIZE_FOR_UPLOADED_FEED) {
                    $this->retrieveUploadedSize($connectorManager, $response, $store);
                }
            }
        }

        return $this;
    }

    /**
     * Perform actions before
     *
     * @param $ids
     * @param $type
     * @param null $store
     * @param array $jobData
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function preProcessActions($ids, $type, $store = null, $jobData = [])
    {
        $this->logger->info('Pre-process execution actions.');

        $isFeedLock = (bool) $this->feedHelper->isFullSynchronizationLocked();
        if ($isFeedLock) {
            if (!$this->lockedTime) {
                $this->lockedTime = microtime(true);
            }
            $this->isFeedLock = true;
            return $this;
        } else {
            $this->isFeedLock = false;
            $this->lockedTime = null;
        }

        // update config status value
        $this->feedHelper->setLastSynchronizationStatus(FeedView::STATUS_RUNNING);

        // filter schema fields (if any)
        $ids = array_filter(
            $ids,
            function ($value) {
                return filter_var($value, FILTER_VALIDATE_INT);
            }
        );



        if ($this->feedViewId) {
            if (!empty($ids)) {
                $this->getFeedViewManager()->updateAffectedEntities($this->feedViewId, $ids);
            }
        } else {
            // create feed view for current API operation
            $feedViewId = $this->getFeedViewManager()->add($ids, $type, $store, $jobData);
            if ($feedViewId) {
                $this->feedViewId = $feedViewId;
            }
        }

        return $this;
    }

    /**
     * Update related config information about current execution
     *
     * @param FeedResponse $response
     * @return $this
     */
    private function updateConfigStats(FeedResponse $response)
    {
        $this->logger->info('Update config statistics.');

        $status = FeedView::STATUS_ERROR;
        if ($response->getIsProcessing()) {
            $status = FeedView::STATUS_INDEXING;
        } else if ($response->getIsSuccess()) {
            $status = FeedView::STATUS_COMPLETE;
        }

        $isSuccess = (bool) $response->getIsSuccess();
        $type = $this->type;

        $this->feedHelper->setFullSynchronizationLocked($this->isFeedLock)
            ->setLastSynchronizationOperationType($type)
            ->setLastSynchronizationDatetime(date('Y-m-d H:i:s'))
            ->setLastSynchronizationStatus($status);

        if ($this->lockedTime) {
            $this->feedHelper->setFullSynchronizationLockedTime($this->lockedTime);
        }
        if ($type == FeedConfig::FEED_TYPE_FULL) {
            $this->feedHelper->setFullCatalogSynchronizedStatus($isSuccess);
        }
        if ($type == FeedConfig::FEED_TYPE_INCREMENTAL) {
            $this->feedHelper->setIncrementalProductSynchronizedStatus($isSuccess);
        }

        $uploadId = $response->getUploadId();
        if ($uploadId) {
            $this->feedHelper->setLastUploadId($uploadId);
        }
        if ($this->uploadedFeedSize > 0) {
            $this->feedHelper->setUploadedSize($this->uploadedFeedSize);
        } else {
            $this->feedHelper->setUploadedSize(FeedConfig::FEED_SIZE_CALCULATION_STATUS);
        }

        return $this;
    }

    /**
     * Update related feed view information about current execution
     *
     * @param FeedResponse $response
     * @return $this
     */
    private function updateFeedView(FeedResponse $response)
    {
        $this->logger->info('Update feed view.');

        $status = FeedView::STATUS_ERROR;
        if ($response->getIsProcessing()) {
            $status = FeedView::STATUS_INDEXING;
        } else if ($response->getIsSuccess()) {
            $status = FeedView::STATUS_COMPLETE;
        }

        if ($this->feedViewId) {
            $updateData = [
                FeedViewInterface::STATUS => $status,
                FeedViewInterface::FINISHED_AT => date('Y-m-d H:i:s'),
                FeedViewInterface::EXECUTION_TIME => $this->logger->getTime(),
                FeedViewInterface::UPLOAD_ID => $response->getUploadId()
            ];
            if ($response->getIsError()) {
                $updateData[FeedViewInterface::ADDITIONAL_INFORMATION] = $response->getErrorsAsString();
            } else if ($response->getIsProcessing()) {
                $updateData[FeedViewInterface::ADDITIONAL_INFORMATION] =
                    __(FeedConfig::FEED_MESSAGE_BY_RESPONSE_TYPE_INDEXING);
            } else if ($response->getIsSuccess()) {
                $updateData[FeedViewInterface::ADDITIONAL_INFORMATION] =
                    __(FeedConfig::FEED_MESSAGE_BY_RESPONSE_TYPE_COMPLETE);
            }
            if ($this->uploadedFeedSize > 0) {
                $message = sprintf(
                    'Total Uploaded Feed Size: %s (children products are not counted)',
                    $this->uploadedFeedSize
                );
                if (empty($updateData[FeedViewInterface::ADDITIONAL_INFORMATION])) {
                    $updateData[FeedViewInterface::ADDITIONAL_INFORMATION] = $message;
                } else {
                    $updateData[FeedViewInterface::ADDITIONAL_INFORMATION] =
                        sprintf(
                            '%s' . '<br/>' . '%s',
                            $updateData[FeedViewInterface::ADDITIONAL_INFORMATION],
                            $message
                        );
                }
            }

            $this->getFeedViewManager()->update($this->feedViewId, $updateData);
        }

        return $this;
    }

    /**
     * Clean cache.
     *
     * @return $this
     */
    private function flushCache()
    {
        $this->logger->info('Flush system configuration cache.');

        try {
            $this->cacheManager->flushByTypes();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $this;
    }

    /**
     * Remove feed files (original and archive if exist) after synchronization
     *
     * @return $this
     */
    private function cleanupFeedFiles()
    {
        $this->logger->info('Cleanup source files.');

        /** @var \Unbxd\ProductFeed\Model\Feed\FileManager $fileManager */
        $fileManager = $this->getFileManager();
        try {
            $fileManager->deleteAffectedFiles();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $this;
    }

    /**
     * Perform actions after
     *
     * @return $this
     */
    public function postProcessActions()
    {
        $this->logger->info('Post-process execution actions.');

        /** @var ApiConnector $connectorManager */
        $connectorManager = $this->getConnectorManager();
        /** @var FeedResponse $response */
        $response = $connectorManager->getResponse();

        if ($response instanceof FeedResponse) {
            if ($response->getIsError()) {
                // log errors if any
                $this->logger->error($response->getErrorsAsString());
            }

            // performing operations with response
            $this->updateConfigStats($response);
            $this->updateFeedView($response);
        }

        //$this->cleanupFeedFiles();

        // reset local cache to initial state
        $this->reset();

        $this->flushCache();

        return $this;
    }

    /**
     * @param $flag
     * @return $this
     */
    private function setIsNeedToArchive($flag)
    {
        $this->isNeedToArchive = (bool) $flag;

        return $this;
    }

    /**
     * @return bool
     */
    private function getIsNeedToArchive()
    {
        return (bool) $this->isNeedToArchive;
    }

    /**
     * Retrieve feed view manager instance. Init if needed
     *
     * @return FeedViewManager
     */
    private function getFeedViewManager()
    {
        return $this->feedViewManager;
    }

    /**
     * Retrieve file manager instance. Init if needed
     *
     * @param array $data
     * @return FileManager|null
     */
    private function getFileManager($data = [])
    {
        if (null == $this->fileManager) {
            /** @var FeedFileManager */
            $this->fileManager = $this->fileManagerFactory->create($data);
        }

        return $this->fileManager;
    }

    /**
     * Reset file manager instance to initial state
     *
     * @return $this
     */
    private function resetFileManager()
    {
        $this->fileManager = null;

        return $this;
    }

    /**
     * Retrieve connector manager instance. Init if needed
     *
     * @param array $data
     * @return Api\Connector|null
     */
    private function getConnectorManager($data = [])
    {
        if (null == $this->connectorManager) {
            /** @var ApiConnector */
            $this->connectorManager = $this->connectorFactory->create($data);
        }

        return $this->connectorManager;
    }

    /**
     * Reset connector manager instance to initial state
     *
     * @return $this
     */
    private function resetConnectorManager()
    {
        $this->connectorManager = null;

        return $this;
    }

    /**
     * Reset all cache handlers to initial state
     *
     * @return void
     */
    private function reset()
    {
        $this->feed = null;
        $this->feedViewId = null;
        $this->type = null;
        $this->isFeedLock = false;
        $this->lockedTime = null;
        $this->uploadedFeedSize = 0;
        $this->resetFileManager()
            ->resetConnectorManager();
    }
}
