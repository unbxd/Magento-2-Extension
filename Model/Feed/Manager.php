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
namespace Unbxd\ProductFeed\Model\Feed;

use Unbxd\ProductFeed\Api\Data\FeedViewInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Product as HelperProduct;
use Unbxd\ProductFeed\Helper\AttributeHelper;
use Unbxd\ProductFeed\Helper\Data as HelperData;
use Unbxd\ProductFeed\Helper\Feed as FeedHelper;
use Unbxd\ProductFeed\Helper\ProductHelper;
use Unbxd\ProductFeed\Helper\Profiler;
use Unbxd\ProductFeed\Model\CacheManager;
use Magento\Framework\UrlInterface;
use Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator;
use Unbxd\ProductFeed\Model\Feed\DataHandler\Image as ImageDataHandler;
use Unbxd\ProductFeed\Model\FeedView;
use Unbxd\ProductFeed\Model\Feed\Config as FeedConfig;
use Unbxd\ProductFeed\Model\FeedView\Handler as FeedViewManager;
use Unbxd\ProductFeed\Model\Feed\FileManager as FeedFileManager;
use Unbxd\ProductFeed\Model\Feed\FileManagerFactory;
use Unbxd\ProductFeed\Model\Feed\Api\Connector as ApiConnector;
use Unbxd\ProductFeed\Model\Feed\Api\ConnectorFactory;
use Unbxd\ProductFeed\Model\Feed\Api\Response as FeedResponse;
use Unbxd\ProductFeed\Logger\LoggerInterface;
use Unbxd\ProductFeed\Logger\OptionsListConstants;
use Unbxd\ProductFeed\Model\Serializer;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Api\SimpleDataObjectConverter;

/**
 * Class Manager
 *
 *  Supported events:
 *   - unbxd_productfeed_prepare_data_before
 *   - unbxd_productfeed_prepare_data_after
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
     * @var AttributeHelper
     */
    private $attributeHelper;

    /**
     * @var HelperData
     */
    private $helperData;

    /**
     * @var FeedHelper
     */
    private $feedHelper;

    /**
     * @var ProductHelper
     */
    private $productHelper;

    /**
     * @var Profiler
     */
    private $profiler;

    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @var FeedConfig
     */
    private $feedConfig;

    /**
     * @var ImageDataHandler
     */
    private $imageDataHandler;

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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var UrlInterface
     */
    private $frontendUrlBuilder;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Prefix of available events for dispatch
     *
     * @var string
     */
    private $eventPrefix = 'unbxd_productfeed';

    /**
     * Cache for product rewrite suffix
     *
     * @var array
     */
    private $productUrlSuffix = [];

    /**
     * Cache for product visibility types
     *
     * @var array
     */
    private $visibility = [];

    /**
     * Feed catalog data
     *
     * @var array
     */
    private $catalog = [];

    /**
     * Feed schema data
     *
     * @var array
     */
    private $schema = [];

    /**
     * Full feed data
     *
     * @var array
     */
    private $fullFeed = [];

    /**
     * Children product(s) schema fields
     *
     * @var array
     */
    private $childrenSchemaFields = [];

    /**
     * Local cache for children product data,
     * in case if child product related to several parent products
     *
     * @var array
     */
    private $childrenData = [];

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
     * Flag to detect if original file content must be archived or not
     *
     * @var bool
     */
    private $isNeedToArchive = true;

    /**
     * Local cache for category list data in specific format
     *
     * @var array
     */
    private $categoryCacheList = [];

    /**
     * Manager constructor.
     * @param AttributeHelper $attributeHelper
     * @param HelperData $helperData
     * @param FeedHelper $feedHelper
     * @param ProductHelper $productHelper
     * @param Profiler $profiler
     * @param CacheManager $cacheManager
     * @param Config $feedConfig
     * @param ImageDataHandler $imageDataHandler
     * @param FeedViewManager $feedViewManager
     * @param \Unbxd\ProductFeed\Model\Feed\FileManagerFactory $fileManagerFactory
     * @param ConnectorFactory $connectorFactory
     * @param LoggerInterface $logger
     * @param UrlInterface $frontendUrlBuilder
     * @param Serializer $serializer
     * @param EventManager $eventManager
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        AttributeHelper $attributeHelper,
        HelperData $helperData,
        FeedHelper $feedHelper,
        ProductHelper $productHelper,
        Profiler $profiler,
        CacheManager $cacheManager,
        FeedConfig $feedConfig,
        ImageDataHandler $imageDataHandler,
        FeedViewManager $feedViewManager,
        FileManagerFactory $fileManagerFactory,
        ConnectorFactory $connectorFactory,
        LoggerInterface $logger,
        UrlInterface $frontendUrlBuilder,
        Serializer $serializer,
        EventManager $eventManager,
        StoreManagerInterface $storeManager
    ) {
        $this->attributeHelper = $attributeHelper;
        $this->helperData = $helperData;
        $this->feedHelper = $feedHelper;
        $this->productHelper = $productHelper;
        $this->profiler = $profiler;
        $this->cacheManager = $cacheManager;
        $this->feedConfig = $feedConfig;
        $this->imageDataHandler = $imageDataHandler;
        $this->feedViewManager = $feedViewManager;
        $this->fileManagerFactory = $fileManagerFactory;
        $this->connectorFactory = $connectorFactory;
        $this->logger = $logger->create(OptionsListConstants::LOGGER_TYPE_FEED);
        $this->frontendUrlBuilder = $frontendUrlBuilder;
        $this->serializer = $serializer ?: \Magento\Framework\App\ObjectManager::getInstance()->get(Serializer::class);
        $this->eventManager = $eventManager;
        $this->storeManager = $storeManager;
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
     * Performing operations related with synchronization with Unbxd service
     *
     * @param $index
     * @param string $type
     * @return bool
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute($index, $type = FeedConfig::FEED_TYPE_FULL)
    {
        if (empty($index)) {
            $this->logger->error('Unable to execute feed. Index data are empty.');
            return false;
        }

        $this->logger->info('START feed execute.')->startTimer();

        $ids = ($type == FeedConfig::FEED_TYPE_FULL) ? [] : array_keys($index);
        $this->preProcessActions($ids, $type);
        if ($this->isFeedLock) {
            $this->lockedTime = round(microtime(true) - $this->lockedTime, 2);
            $this->logger->error(
                'Unable to execute feed. Feed lock by another process. Locked time: ' . $this->lockedTime
            );
            return false;
        }

        // caching feed operation type
        $this->type = $type;

        $this->startProfiler();
        $this->prepareData($index);
        $this->buildFeed();
        $this->serializeFeed();
        $this->writeFeed();
        $this->sendFeed();
        $this->postProcessActions();

        $this->logger->info('END feed execute. STATS:')->logStats();
        $this->stopProfiler();

        return true;
    }

    /**
     * Prepare index data for feed operations
     *
     * @param array $index
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function prepareData(array $index)
    {
        $this->logger->info('Prepare feed content based on index data.');
        $this->logger->info('Dispatch event: ' . $this->eventPrefix . '_prepare_data_before.');
        $this->eventManager->dispatch($this->eventPrefix . '_prepare_data_before',
            ['index' => $index, 'feed_manager' => $this]
        );

        $this->buildCatalogData($index);

        $schemaFields = array_key_exists('fields', $index) ? $index['fields'] : false;
        if ($schemaFields) {
            $this->buildSchemaFields($schemaFields);
            unset($index['fields']);
        }

        $this->logger->info('Dispatch event: ' . $this->eventPrefix . '_prepare_data_after.');
        $this->eventManager->dispatch($this->eventPrefix . '_prepare_data_after',
            ['index' => $index, 'feed_manager' => $this]
        );

        return $this;
    }

    /**
     * @param array $fields
     * @return $this
     */
    private function appendChildFieldsToSchema(array &$fields)
    {
        // try to add children fields to schema
        if (!empty($this->childrenSchemaFields)) {
            foreach (array_values($this->childrenSchemaFields) as $childField) {
                // add only fields that already exist in schema fields
                if (array_key_exists($childField, $fields)) {
                    $childKey = sprintf(
                        '%s%s',
                        FeedConfig::CHILD_PRODUCT_FIELD_PREFIX,
                        ucfirst(SimpleDataObjectConverter::snakeCaseToCamelCase($childField))
                    );
                    if (!array_key_exists($childKey, $fields)) {
                        $childFieldData = $fields[$childField];
                        if (!empty($childFieldData)) {
                            $childFieldData['fieldName'] = $childKey;
                            $fields[$childKey] = $childFieldData;
                        }
                    }
                } else if ($childField == FeedConfig::CHILD_PRODUCT_FIELD_VARIANT_ID) {
                    // field 'variant_id' doesn't exist in main schema fields, add it manually
                    $childField = SimpleDataObjectConverter::snakeCaseToCamelCase($childField);
                    $fields[$childField] = [
                        'fieldName' => $childField,
                        'dataType' => FeedConfig::FIELD_TYPE_TEXT,
                        'multiValued' => false,
                        'autoSuggest' => FeedConfig::DEFAULT_SCHEMA_AUTO_SUGGEST_FIELD_VALUE
                    ];
                }
            }
        }

        return $this;
    }

    /**
     * @param array $fields
     * @return $this
     */
    private function buildSchemaFields(array $fields)
    {
        if (empty($fields)) {
            $this->logger->info('Can\'t prepare schema fields. Index data is empty.');
            return $this;
        }

        // add main product fields to schema
        $excludedFields = $this->feedConfig->getExcludedFields();
        $mapSpecificFields = $this->feedConfig->getMapSpecificFields();
        foreach ($fields as $fieldCode => &$fieldData) {
            if (in_array($fieldCode, $excludedFields)) {
                unset($fields[$fieldCode]);
            }
            if (array_key_exists($fieldCode, $mapSpecificFields)) {
                $fieldKey = $mapSpecificFields[$fieldCode];
                $fieldData['fieldName'] = $fieldKey;
                $fields[$fieldKey] = $fieldData;
                unset($fields[$fieldCode]);
            }

            // convert to needed format
            $fieldData['fieldName'] = SimpleDataObjectConverter::snakeCaseToCamelCase($fieldData['fieldName']);
        }

        $this->appendChildFieldsToSchema($fields);

        $this->schema = [
            Config::SCHEMA_FIELD_KEY => array_values($fields)
        ];

        return $this;
    }

    /**
     * @param array $index
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function buildCatalogData(array $index)
    {
        if (empty($index)) {
            $this->logger->error('Can\'t prepare catalog data. Index data is empty.');
            return $this;
        }

        $catalog = [];
        foreach ($index as $productId => &$data) {
            // schema fields has key 'fields', do only for products
            if (is_int($productId)) {
                // prepare main feed fields
                $this->prepareMainFields($data);

                // append child data to parent
                if (
                    isset($data[Config::CHILD_PRODUCT_IDS_FIELD_KEY])
                    && !empty($data[Config::CHILD_PRODUCT_IDS_FIELD_KEY])
                ) {
                    $currentChildIds = $data[Config::CHILD_PRODUCT_IDS_FIELD_KEY];
                    $this->appendChildDataToParent($index, $data, $currentChildIds);
                } else {
                    // if product doesn't have children - add empty variants data
                    $data[Config::CHILD_PRODUCTS_FIELD_KEY] = [];
                }

                // filter index data helper fields
                $this->filterFields($data);

                // check if product related to parent product (variant product),
                // if so - do not add child to feed catalog data, just add it like variant product
                if (isset($data[Config::PARENT_ID_KEY])) {
                    unset($data[Config::PARENT_ID_KEY]);
                    continue;
                }

                // change array keys to needed format
                $this->formatArrayKeysToCamelCase($data);

                // remove helper field
                if (isset($data[Config::PREPARED_FIELDS_KEY])) {
                    unset($data[Config::PREPARED_FIELDS_KEY]);
                }

                // combine data by type of operations
                $operationKey = array_key_exists('action', $data)
                    ? trim($data['action'])
                    : Config::OPERATION_TYPE_ADD;

                // if operation type is 'delete' uniqueId is only one required field
                if ($operationKey == Config::OPERATION_TYPE_DELETE) {
                    $key = SimpleDataObjectConverter::snakeCaseToCamelCase(Config::SPECIFIC_FIELD_KEY_UNIQUE_ID);
                    $data = [$key => $productId];
                }

                $catalog[$operationKey][Config::CATALOG_ITEMS_FIELD_KEY][] = $data;
            }
        }

        if (!empty($catalog)) {
            $this->catalog = $catalog;
        }

        return $this;
    }

    /**
     * Build feed content to needed format based on prepared index data
     *
     * @return $this|bool
     */
    public function buildFeed()
    {
        $this->logger->info('Build feed content.');

        // check only catalog data, we don't need to check schema fields
        // in case if 'delete' operation will be performing, scheme fields are not required
        if (empty($this->catalog)) {
            $this->logger->info('Can\'t build feed content. Prepared data is empty.');
            return false;
        }

        if (!empty($this->schema) && Config::INCLUDE_SCHEMA) {
            $this->fullFeed = array_merge($this->fullFeed, $this->schema);
        }

        if (!empty($this->catalog) && Config::INCLUDE_CATALOG) {
            $this->fullFeed = array_merge($this->fullFeed, $this->catalog);
        }

        if (!empty($this->fullFeed)) {
            $this->fullFeed = [
                FeedConfig::FEED_FIELD_KEY => [
                    FeedConfig::CATALOG_FIELD_KEY => $this->fullFeed
                ]
            ];
        }
		
		// reset local cache for main feed parts
		$this->schema = $this->catalog = [];

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

        if ($this->fullFeed) {
            try {
                $this->fullFeed = $this->serializer->serializeToJson($this->fullFeed);
            } catch (\Exception $e) {
                $this->logger->critical($e);
                $this->postProcessActions();
            }
        }

        return $this;
    }

    /**
     * Write feed content to file
     *
     * @return $this
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function writeFeed()
    {
        $this->logger->info('Write feed content to file.');

        /** @var \Unbxd\ProductFeed\Model\Feed\FileManager $fileManager */
        $fileManager = $this->getFileManager();
        if ($this->fullFeed) {
            // remove old file if exist
            if ($fileManager->isExist()) {
                $fileManager->deleteFile();
            }
			
			try {
				$fileManager->write($this->fullFeed);
			} catch (\Exception $e) {
				$this->logger->critical($e);
				$this->postProcessActions();
				return $this;
			}
        }

        // reset local cache for feed content
        $this->fullFeed = [];

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
     * @return $this
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function retrieveUploadedSize(ApiConnector $connectorManager, FeedResponse $response)
    {
        $this->logger->info('Retrieve uploaded feed size.');

        try {
            $connectorManager->resetHeaders()
                ->resetParams()
                ->execute(FeedConfig::FEED_TYPE_UPLOADED_SIZE, \Zend_Http_Client::GET);
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
     * @return $this
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function checkUploadedFeedStatus(ApiConnector $connectorManager, FeedResponse $response)
    {
        $this->logger->info('Check uploaded feed status.');

        $this->logger->info('Dispatch event: ' . $this->eventPrefix . '_uploaded_status_before.');
        $this->eventManager->dispatch($this->eventPrefix . '_uploaded_status_before',
            ['response' => $response, 'feed_manager' => $this]
        );

        $apiEndpointType = ($this->type == FeedConfig::FEED_TYPE_FULL)
            ? FeedConfig::FEED_TYPE_FULL_UPLOADED_STATUS
            : FeedConfig::FEED_TYPE_INCREMENTAL_UPLOADED_STATUS;

        try {
            $connectorManager->resetHeaders()
                ->resetParams()
                ->execute($apiEndpointType, \Zend_Http_Client::GET);
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->postProcessActions();
            return $this;
        }

        $this->logger->info('Dispatch event: ' . $this->eventPrefix . '_uploaded_status_after.');
        $this->eventManager->dispatch($this->eventPrefix . '_uploaded_status_after',
            ['response' => $response, 'feed_manager' => $this]
        );

        return $this;
    }

    /**
     * Send feed data through Unbxd API
     *
     * @return $this
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function sendFeed()
    {
        $this->logger->info('Send feed to service.');

        $params = $this->buildFileParameters();
        if (empty($params)) {
            $this->logger->error('File parameters for request are empty.');
            return $this;
        }

        $this->logger->info('Dispatch event: ' . $this->eventPrefix . '_send_before.');
        $this->eventManager->dispatch($this->eventPrefix . '_send_before',
            ['file_params' => $params, 'feed_manager' => $this]
        );

        /** @var \Unbxd\ProductFeed\Model\Feed\Api\Connector $connectorManager */
        $connectorManager = $this->getConnectorManager();
        try {
            $connectorManager->execute($this->type,\Zend_Http_Client::POST, [], $params);
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->postProcessActions();
            return $this;
        }

        $this->logger->info('Dispatch event: ' . $this->eventPrefix . '_send_after.');
        $this->eventManager->dispatch($this->eventPrefix . '_send_after',
            ['file_params' => $params, 'feed_manager' => $this]
        );

        /** @var FeedResponse $response */
        $response = $connectorManager->getResponse();
        if ($response instanceof FeedResponse) {
            if (!$response->getIsError()) {
                // additional API calls
                if (FeedConfig::VALIDATE_STATUS_FOR_UPLOADED_FEED) {
                    $this->checkUploadedFeedStatus($connectorManager, $response);
                }
                if (FeedConfig::RETRIEVE_SIZE_FOR_UPLOADED_FEED) {
                    $this->retrieveUploadedSize($connectorManager, $response);
                }
            }
        }

        return $this;
    }

    /**
     * Perform actions before
     *
     * @param array $ids
     * @param string $type
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function preProcessActions($ids, $type)
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

        // @TODO - need to figure out with stores
        $storeId = 1;
        // create feed view for current API operation
        $feedViewId = $this->getFeedViewManager()->add($ids, $type, $storeId);
        if ($feedViewId) {
            $this->feedViewId = $feedViewId;
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
     * Clean configuration cache.
     * In some cases related config info doesn't refreshing on backend frontend
     *
     * @return $this
     */
    private function flushSystemConfigCache()
    {
        $this->logger->info('Flush system configuration cache.');

        try {
            $this->cacheManager->flushSystemConfigCache();
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
            $fileManager->deleteSourcePath();
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

        // reset local cache to initial state
        $this->reset();

        $this->cleanupFeedFiles();

        // in some cases related config info doesn't refreshing on backend frontend
        $this->flushSystemConfigCache();

        return $this;
    }

    /**
     * Prepare main feed fields to according format
     *
     * @param array $data
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function prepareMainFields(array &$data)
    {
        $websiteIdKey = 'website_id';
        $entityIdKey = Config::FIELD_KEY_ENTITY_ID;
        $nameKey = Config::FIELD_KEY_PRODUCT_NAME;
        $urlKeyKey = Config::FIELD_KEY_PRODUCT_URL_KEY;
        $imageKey = Config::FIELD_KEY_IMAGE_PATH;
        $categoryKey = Config::FIELD_KEY_CATEGORY_DATA;
        $visibilityKey = ProductInterface::VISIBILITY;

        // prepare store field
        $defaultStoreId = $this->getStore()->getId();
        $storeId = isset($data[Store::STORE_ID]) ? $data[Store::STORE_ID] : $defaultStoreId;
        $data[Store::STORE_ID] = $storeId;
        // prepare website field
        $websiteId = isset($data[$websiteIdKey])
            ? $data[$websiteIdKey]
            : $this->getWebsite($defaultStoreId)->getId();
        $data[$websiteIdKey] = $websiteId;
        // prepare title field
        if (isset($data[$nameKey]) && !empty($data[$nameKey])) {
            $value = is_array($data[$nameKey]) ? array_pop($data[$nameKey]) : $data[$nameKey];
            $data[Config::SPECIFIC_FIELD_KEY_TITLE] = $value;
            unset($data[$nameKey]);
        }
        // prepare unique_id field
        if (isset($data[$entityIdKey]) && !empty($data[$entityIdKey])) {
            $data[Config::SPECIFIC_FIELD_KEY_UNIQUE_ID] = $data[$entityIdKey];
            unset($data[$entityIdKey]);
        }
        // prepare product_url field
        if (isset($data[$urlKeyKey]) && !empty($data[$urlKeyKey])) {
            $value = is_array($data[$urlKeyKey]) ? array_pop($data[$urlKeyKey]) : $data[$urlKeyKey];
            $productUrl = $this->buildProductUrl($value, $storeId);
            if ($productUrl) {
                $data[Config::SPECIFIC_FIELD_KEY_PRODUCT_URL] = $productUrl;
                unset($data[$urlKeyKey]);
            }
        }
        // prepare image_url field
        if (isset($data[$imageKey]) && !empty($data[$imageKey])) {
            $value = is_array($data[$imageKey]) ? array_pop($data[$imageKey]) : $data[$imageKey];
            $imageUrl = $this->imageDataHandler->getImageUrl($value);
            if ($imageUrl) {
                $data[Config::SPECIFIC_FIELD_KEY_IMAGE_URL] = $imageUrl;
                unset($data[$imageKey]);
            }
        }
        // prepare category_path_id field
        if (isset($data[$categoryKey]) && !empty($data[$categoryKey])) {
            $categoryData = $this->buildCategoryList($data[$categoryKey]);
            if (!empty($categoryData)) {
                $data[Config::SPECIFIC_FIELD_KEY_CATEGORY_PATH_ID] = $categoryData;
            }
            unset($data[$categoryKey]);
        }
        // prepare visibility field
        if (isset($data[$visibilityKey]) && !empty($data[$visibilityKey])) {
            $value = is_array($data[$visibilityKey])
                ? array_pop($data[$visibilityKey])
                : $data[$visibilityKey];
            $data[$visibilityKey] = $this->getVisibilityTypeLabel($value);
        }

        return $this;
    }

    /**\
     * @param array $index
     * @param array $parentData
     * @param array $childIds
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function appendChildDataToParent(array &$index, array &$parentData, array $childIds)
    {
        foreach ($childIds as $id) {
            if (!array_key_exists($id, $index)) {
                continue;
            }

            if (!isset($index[$id][Config::getPreparedKey()])) {
                // in case if child child data not prepared yet
                $this->prepareMainFields($index[$id]);
                $this->filterFields($index[$id]);
            }

            $childData = $this->formatChildData($index[$id], $id);
            if (!empty($childData)) {
                $parentData[Config::CHILD_PRODUCTS_FIELD_KEY][] = $childData;
            }
        }

        return $this;
    }

    /**
     * @param array $data
     * @param $childId
     * @return array
     */
    private function formatChildData(array $data, $childId)
    {
        if (!isset($this->childrenData[$childId])) {
            // remove helper fields from child data if any
            $excludedFields = [
                Config::CHILD_PRODUCTS_FIELD_KEY,
                Config::PARENT_ID_KEY,
                Config::PREPARED_FIELDS_KEY,
                Config::getPreparedKey()
            ];
            foreach ($excludedFields as $field) {
                if (array_key_exists($field, $data)) {
                    unset($data[$field]);
                }
            }

            $variantIdKey = SimpleDataObjectConverter::snakeCaseToCamelCase(Config::CHILD_PRODUCT_FIELD_VARIANT_ID);
            foreach ($data as $key => $value) {
                // map child fields to use for add to schema fields
                if (!in_array($key, $this->childrenSchemaFields)) {
                    $this->childrenSchemaFields[$key] = $key;
                    if ($key == Config::SPECIFIC_FIELD_KEY_UNIQUE_ID) {
                        $this->childrenSchemaFields[$key] = Config::CHILD_PRODUCT_FIELD_VARIANT_ID;
                    }
                }
                $newKey = sprintf(
                    '%s%s',
                    Config::CHILD_PRODUCT_FIELD_PREFIX,
                    ucfirst(SimpleDataObjectConverter::snakeCaseToCamelCase($key))
                );

                if (
                in_array($key, [
                        Config::SPECIFIC_FIELD_KEY_UNIQUE_ID,
                        SimpleDataObjectConverter::snakeCaseToCamelCase(Config::SPECIFIC_FIELD_KEY_UNIQUE_ID)
                    ]
                )
                ) {
                    $newKey = $variantIdKey;
                }

                $data[$newKey] = $value;
                if ($newKey != $key) {
                    unset($data[$key]);
                }
            }

            if (!isset($data[$variantIdKey])) {
                // omit children product if variant id is not specified
                return [];
            }

            $this->childrenData[$childId] = $data;
        }

        return $this->childrenData[$childId];
    }

    /**
     * @param array $data
     * @return $this
     */
    private function filterParentFieldsChildrenAttributes(array &$data)
    {
        if (array_key_exists(Config::CHILD_PRODUCT_ATTRIBUTES_FIELD_KEY, $data)) {
            foreach ($data[Config::CHILD_PRODUCT_ATTRIBUTES_FIELD_KEY] as $attributeCode) {
                if (array_key_exists($attributeCode, $data)) {
                    unset($data[$attributeCode]);
                }
            }
        }

        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    private function filterParentFieldsChildrenRelated(array &$data)
    {
        $excludedFields = $this->feedConfig->getParentChildrenRelatedFields();
        if (!empty($excludedFields)) {
            foreach ($excludedFields as $field) {
                if (array_key_exists($field, $data)) {
                    unset($data[$field]);
                }
            }
        }

        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    private function filterAdditionalFields(array &$data)
    {
        $excludedFields = $this->feedConfig->getExcludedFields();
        if (!empty($excludedFields)) {
            foreach ($excludedFields as $field) {
                if (array_key_exists($field, $data)) {
                    unset($data[$field]);
                }
            }
        }

        return $this;
    }

    /**
     * @param array $data
     */
    private function prepareOptionValues(array &$data)
    {
        $matchKeyPart = 'option_text_';
        foreach ($data as $key => $value) {
            $pureKey = str_replace($matchKeyPart, '', $key);
            $searchKey = $matchKeyPart . $key;
            if (strpos($key, $matchKeyPart) !== false) {
                // fields with option values
                $data[$pureKey] = array_key_exists($searchKey, $data)
                    ? implode(',', $data[$searchKey])
                    : (
                    // format to string array only with one record, otherwise put it as is
                    (is_array($value) && (count($value) == 1) && ($key != Config::SPECIFIC_FIELD_KEY_CATEGORY_PATH_ID))
                        ? implode(',', $value)
                        : $value
                    );
            } else {
                $excluded = [
                    Config::SPECIFIC_FIELD_KEY_CATEGORY_PATH_ID,
                    Config::CHILD_PRODUCTS_FIELD_KEY
                ];

                // format to string array only with one record, otherwise put it as is
                $data[$key] = (is_array($value) && (count($value) == 1) && !in_array($key, $excluded))
                    ? implode(',', $value)
                    : $value;
            }
            unset($data[$searchKey]);
        }
    }

    /**
     * Filter fields in feed data
     *
     * @param array $data
     * @return $this
     */
    private function filterFields(array &$data)
    {
        // remove attributes fields related to child products
        $this->filterParentFieldsChildrenAttributes($data);

        // remove fields related to child products
        $this->filterParentFieldsChildrenRelated($data);

        // index helper fields which must be deleted from feed content
        $this->filterAdditionalFields($data);

        // convert option values and labels only in labels
        $this->prepareOptionValues($data);

        // mark to prevent not prepared data
        if (!isset($data[Config::getPreparedKey()])) {
            $data[Config::getPreparedKey()] = true;
        }

        return $this;
    }

    /**
     * Build category list related to product in specific format supported by Unbxd service
     * (ex.: /fashion|Fashion>/fashion/shoes|Shoes>/fashion/shoes/casual|Casual)
     *
     * @param $categoryData
     * @return array
     */
    private function buildCategoryList($categoryData)
    {
        $result = [];
        foreach ($categoryData as $data) {
            $categoryId = isset($data['category_id']) ? (int) $data['category_id'] : null;
            // try to retrieve category list data from cache
            if (isset($this->categoryCacheList[$categoryId])) {
                $result[] = $this->categoryCacheList[$categoryId];
                continue;
            }

            $name = isset($data['name']) ? (string) trim($data['name']) : null;
            $urlPath = isset($data['url_path'])
                ? (string) trim($data['url_path'], '/')
                    : (isset($data['url_key']) ? (string) trim($data['url_key'], '/') : null);
            if (!$name || !$urlPath) {
                continue;
            }

            // remove double slashes from path if any
            $urlPath = preg_replace('#/+#','/', $urlPath);
            if (!$urlPath) {
                continue;
            }

            if (strpos($urlPath, '/') !== false) {
                $pathData = explode('/', $urlPath);
            } else {
                $pathData = [$urlPath];
            }

            if (!empty($pathData)) {
                $path = '';
                $urlPart = '';
                foreach ($pathData as $urlKey) {
                    $key = array_search($urlKey, array_column($categoryData, 'url_key'));
                    $name = ucwords(trim(str_replace('-', ' ', strtolower($urlKey))));
                    if ($key && isset($categoryData[$key]['name'])) {
                        $name = trim($categoryData[$key]['name']);
                    }

                    $urlPart .= '/' . $urlKey;
                    $path .= sprintf('%s|%s>', $urlPart, $name);
                }
                $pathString = rtrim(trim($path, '>'), '/');
                $result[] = $pathString;

                $this->categoryCacheList[$categoryId] = $pathString;
            }
        }

        return array_unique($result, SORT_REGULAR);
    }

    /**
     * Retrieve product frontend url
     *
     * @param $urlKey
     * @param $storeId
     * @return mixed
     */
    private function buildProductUrl($urlKey, $storeId)
    {
        $path = $urlKey . $this->getProductUrlSuffix($storeId);
        $url = $this->getFrontendUrl($path);
        // check if use category path for product url
        if ($this->helperData->isSetFlag(HelperProduct::XML_PATH_PRODUCT_URL_USE_CATEGORY)) {
            // @TODO - we need to implement this?
        }

        return (substr($url, -1) == '/') ? substr($url, 0, -1) : $url;
    }

    /**
     * Retrieve product rewrite suffix for store
     *
     * @param int $storeId
     * @return string
     */
    private function getProductUrlSuffix($storeId)
    {
        if (!isset($this->productUrlSuffix[$storeId])) {
            $this->productUrlSuffix[$storeId] = $this->helperData->getConfigValue(
                ProductUrlPathGenerator::XML_PATH_PRODUCT_URL_SUFFIX
            );
        }

        return $this->productUrlSuffix[$storeId];
    }

    /**
     * Retrieve product visibility label by value
     *
     * @param string $value
     * @return mixed
     */
    private function getVisibilityTypeLabel($value)
    {
        if (!isset($this->visibility[$value])) {
            $this->visibility[$value] = (string) $this->productHelper->getVisibilityTypeLabelByValue($value);
        }

        return $this->visibility[$value];
    }

    /**
     * Get frontend url
     *
     * @param $routePath
     * @param string $scope
     * @return mixed
     */
    public function getFrontendUrl($routePath, $scope = '')
    {
        $this->frontendUrlBuilder->setScope($scope);
        $href = $this->frontendUrlBuilder->getUrl(
            $routePath,
            [
                '_current' => false,
                '_nosid' => true,
                '_query' => false
            ]
        );

        return $href;
    }

    /**
     * @param array $data
     * @return $this
     */
    private function formatArrayKeysToCamelCase(array &$data)
    {
        foreach ($data as $key => $value) {
            $newKey = SimpleDataObjectConverter::snakeCaseToCamelCase($key);
            $data[$newKey] = $value;
            if ($newKey != $key) {
                unset($data[$key]);
            }
        }

        return $this;
    }

    /**
     * @param string $storeId
     * @return \Magento\Store\Api\Data\StoreInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getStore($storeId = '')
    {
        return $this->storeManager->getStore($storeId);
    }

    /**
     * @param string $storeId
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getWebsite($storeId = '')
    {
        return $this->getStore($storeId)->getWebsite();
    }

    /**
     * @param $flag
     */
    private function setIsNeedToArchive($flag)
    {
        $this->isNeedToArchive = (bool) $flag;
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
            /** @var \Unbxd\ProductFeed\Model\Feed\FileManager */
            $this->fileManager = $this->fileManagerFactory->create($data);
        }

        return $this->fileManager;
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
     * Reset all cache handlers to initial state
     *
     * @return void
     */
    private function reset()
    {
        $this->schema = [];
        $this->catalog = [];
        $this->fullFeed = [];
        $this->productUrlSuffix = [];
        $this->visibility = [];
        $this->childrenSchemaFields = [];
        $this->childrenData = [];
        $this->type = null;
        $this->isFeedLock = false;
        $this->lockedTime = null;
        $this->feedViewId = null;
        $this->uploadedFeedSize = 0;
    }
}