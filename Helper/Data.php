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
namespace Unbxd\ProductFeed\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface as ConfigWriter;
use Magento\Framework\App\Config\ValueInterface as ConfigValueInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Unbxd\ProductFeed\Model\Config\Backend\Cron\General as CronGeneral;
use Unbxd\ProductFeed\Model\Config\Backend\Cron\FullFeed as CronFullFeed;
use Unbxd\ProductFeed\Model\Config\Source\ProductTypes;
use Unbxd\ProductFeed\Model\Config\Source\FilterAttribute;
use Unbxd\ProductFeed\Model\FilterAttribute\FilterAttributeProvider;
use Unbxd\ProductFeed\Model\FilterAttribute\FilterAttributeInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\App\ObjectManager;
use Unbxd\ProductFeed\Model\Serializer;
use Magento\Framework\App\ResourceConnection;

/**
 * Class Data
 * @package Unbxd\ProductFeed\Helper
 */
class Data extends AbstractHelper
{
    /**
     * XML paths
     *
     * setup section
     */
    const XML_PATH_SETUP_SITE_KEY = 'unbxd_setup/general/site_key';
    const XML_PATH_SETUP_SECRET_KEY = 'unbxd_setup/general/secret_key';
    const XML_PATH_SETUP_API_KEY = 'unbxd_setup/general/api_key';

    /**
     * API endpoints
     */
    const XML_PATH_FULL_FEED_API_ENDPOINT = 'unbxd_setup/api_endpoints/full';
    const XML_PATH_INCREMENTAL_FEED_API_ENDPOINT = 'unbxd_setup/api_endpoints/incremental';
    const XML_PATH_FULL_UPLOADED_STATUS = 'unbxd_setup/api_endpoints/full_uploaded_status';
    const XML_PATH_INCREMENTAL_UPLOADED_STATUS = 'unbxd_setup/api_endpoints/incremental_uploaded_status';
    const XML_PATH_UPLOADED_SIZE = 'unbxd_setup/api_endpoints/uploaded_size';

    /**
     * catalog section
     */
    const XML_PATH_CATALOG_AVAILABLE_PRODUCT_TYPES = 'unbxd_catalog/general/available_product_types';
    const XML_PATH_NUMBER_OF_VARIANTS = 'unbxd_catalog/general/number_of_variants';
    const XML_PATH_CATALOG_EXCLUDE_PRODUCTS_FILTER_ATTRIBUTES = 'unbxd_catalog/general/filter_attributes';
    const XML_PATH_CATALOG_MAX_NUMBER_OF_ATTEMPTS = 'unbxd_catalog/general/max_number_of_attempts';
    
    /**
     * Indexing Settings
     */
    const XML_PATH_CATALOG_INDEXING_QUEUE_ENABLED = 'unbxd_catalog/indexing/enabled_queue';
    const XML_PATH_CATALOG_INDEXING_PARTIAL_INCREMENTAL_ENABLED = 'unbxd_catalog/indexing/incremental_partial_update';
    const XML_PATH_CATALOG_MULTI_PART_UPLOAD_ENABLED = 'unbxd_catalog/indexing/multi_part_upload';
    const XML_PATH_CATALOG_BATCH_SIZE = 'unbxd_catalog/indexing/batch_size';
    const XML_PATH_CATALOG_MULTI_PART_BATCH_SIZE = 'unbxd_catalog/indexing/multi_part_batch_size';
    const XML_PATH_INDEXING_QUEUE_ARCHIVAL_INMINUTES = 'unbxd_catalog/indexing/indexing_queue_archival_time';
    const XML_PATH_FEED_VIEW_ARCHIVAL_INMINUTES = 'unbxd_catalog/indexing/feed_view_archival_time';
    const XML_PATH_FEED_FILE_CLEANUP = 'unbxd_catalog/indexing/feed_file_cleanup_enabled';

    const XML_PATH_CATALOG_DATA_FIELDS_MAPPING_SETTINGS = 'unbxd_catalog/data_fields_mapping/mapping_settings';
    const XML_PATH_CATALOG_VERSION_CHECK = 'unbxd_catalog/general/check_latest_version_update';
    const XML_PATH_FETCH_FROM_CATEGORY_TABLES = 'unbxd_catalog/general/fetch_from_category_tables';
    const XML_PATH_USE_CATEGORY_ID = 'unbxd_catalog/general/use_categoryid_insteadof_path';
    const XML_PATH_RETAIN_INACTIVE_CATEGORY = 'unbxd_catalog/general/retain_inactive_category';
    const XML_PATH_RETAIN_ROOT_CATEGORY = 'unbxd_catalog/general/retain_root_category';

    /**
     * product images settings
     */
    const XML_PATH_IMAGES_USE_CACHED_PRODUCT_IMAGES = 'unbxd_catalog/images/use_cached_product_images';
    const XML_PATH_IMAGES_RESIZE_IMAGE_WHEN_NOT_FOUND = 'unbxd_catalog/images/resize_image_when_not_found';
    const XML_PATH_IMAGES_REMOVE_PUB_MEDIA_URL = 'unbxd_catalog/images/remove_pub_directory_in_mediaurl';
    const XML_PATH_IMAGES_BASE_IMAGE_ID = 'unbxd_catalog/images/base_image_id';
    const XML_PATH_IMAGES_SMALL_IMAGE_ID = 'unbxd_catalog/images/small_image_id';
    const XML_PATH_IMAGES_THUMBNAIL_ID = 'unbxd_catalog/images/thumbnail_id';
    const XML_PATH_IMAGES_SWATCH_IMAGE_ID = 'unbxd_catalog/images/swatch_image_id';
    /**
     * general cron settings
     */
    const XML_PATH_CATALOG_CRON_GENERAL_ENABLED = 'unbxd_catalog/cron/general_settings/enabled';
    const XML_PATH_CATALOG_CRON_GENERAL_TYPE = 'unbxd_catalog/cron/general_settings/cron_type';
    const XML_PATH_CATALOG_CRON_GENERAL_TYPE_MANUALLY_SCHEDULE = 'unbxd_catalog/cron/general_settings/cron_type_manually_schedule';
    const XML_PATH_CATALOG_CRON_GENERAL_TYPE_TEMPLATE_TIME = 'unbxd_catalog/cron/general_settings/cron_type_template_time';
    const XML_PATH_CATALOG_CRON_GENERAL_TYPE_TEMPLATE_FREQUENCY = 'unbxd_catalog/cron/general_settings/cron_type_template_frequency';
    /**
     * full feed cron settings
     */
    const XML_PATH_CATALOG_CRON_FULL_ENABLED = 'unbxd_catalog/cron/full_feed_settings/enabled';
    const XML_PATH_CATALOG_CRON_FULL_TYPE = 'unbxd_catalog/cron/full_feed_settings/cron_type';
    const XML_PATH_CATALOG_CRON_FULL_TYPE_MANUALLY_SCHEDULE = 'unbxd_catalog/cron/full_feed_settings/cron_type_manually_schedule';
    const XML_PATH_CATALOG_CRON_FULL_TYPE_TEMPLATE_TIME = 'unbxd_catalog/cron/full_feed_settings/cron_type_template_time';
    const XML_PATH_CATALOG_CRON_FULL_TYPE_TEMPLATE_FREQUENCY = 'unbxd_catalog/cron/full_feed_settings/cron_type_template_frequency';
    /**
     * manual sync settings
     */
    const XML_PATH_CATALOG_MANUAL_SYNCHRONIZATION_ENABLED = 'unbxd_catalog/actions/enabled';

    /**
     * Feed Settings
     */
    const XML_PATH_CATALOG_FEED_STREAMING_ENABLED = 'unbxd_catalog/feed/enable_stream_serialization';

    const XML_PATH_CATALOG_FEED_READER_DB_CONNECTION_NAME = 'unbxd_catalog/feed/reader_db_connection';


    /**
     * @var ConfigInterface
     */
    private $configInterface;

    /**
     * @var ConfigWriter
     */
    private $configWriter;

    /**
     * @var ConfigValueInterface
     */
    private $configData;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ProductTypes
     */
    protected $productTypes;

    /**
     * @var FilterAttributeProvider
     */
    protected $filterAttributeProvider;

    /**
     * @var TimezoneInterface
     */
    protected $dateTime;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var null
     */
    private $dataFieldsMapping = null;

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param ConfigInterface $configInterface
     * @param ConfigWriter $configWriter
     * @param ConfigValueInterface $configData
     * @param StoreManagerInterface $storeManager
     * @param ProductTypes $productTypes
     * @param FilterAttributeProvider $filterAttributeProvider
     * @param TimezoneInterface $dateTime
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        ConfigInterface $configInterface,
        ConfigWriter $configWriter,
        ConfigValueInterface $configData,
        StoreManagerInterface $storeManager,
        ProductTypes $productTypes,
        FilterAttributeProvider $filterAttributeProvider,
        TimezoneInterface $dateTime,
        Serializer $serializer
    ) {
        parent::__construct($context);
        $this->configInterface = $configInterface;
        $this->configWriter = $configWriter;
        $this->configData = $configData;
        $this->storeManager = $storeManager;
        $this->productTypes = $productTypes;
        $this->filterAttributeProvider = $filterAttributeProvider;
        $this->dateTime = $dateTime;
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(Serializer::class);
    }

    /**
     * Retrieve core config value by path and store
     *
     * @param $path
     * @param string $scopeType
     * @param null $scopeCode
     * @return string
     */
    public function getConfigValue($path, $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeCode = null)
    {
        return trim($this->scopeConfig->getValue($path, $scopeType, $scopeCode)?? '');
    }

    /**
     * Save config value to storage
     *
     * @param $path
     * @param $value
     * @param string $scope
     * @param int $scopeId
     */
    public function updateConfigValue($path, $value, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0)
    {
        $this->configWriter->save($path, trim($value?? ''), $scope, $scopeId);
    }

    /**
     * Save config value to the storage resource
     *
     * @param $path
     * @param $value
     * @param string $scope
     * @param int $scopeId
     */
    public function saveConfig($path, $value, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0)
    {
        $this->configInterface->saveConfig($path, $value, $scope, $scopeId);
    }

    /**
     * Delete config value from the storage resource
     *
     * @param $path
     * @param string $scope
     * @param int $scopeId
     */
    public function deleteConfig($path, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0)
    {
        $this->configInterface->deleteConfig($path, $scope, $scopeId);
    }

    /**
     * Check whether or not core config value is enabled
     *
     * @param $path
     * @param string $scopeType
     * @param null $scopeCode
     * @return bool
     */
    public function isSetFlag($path, $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeCode = null)
    {
        return $this->scopeConfig->isSetFlag($path, $scopeType, $scopeCode);
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function getReaderConnectionName($store = null){
        return trim($this->scopeConfig->getValue(
            self::XML_PATH_CATALOG_FEED_READER_DB_CONNECTION_NAME,
            ScopeInterface::SCOPE_STORE,
            $store
        )??ResourceConnection::DEFAULT_CONNECTION);
        
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function getSiteKey($store = null)
    {
        return trim($this->scopeConfig->getValue(
            self::XML_PATH_SETUP_SITE_KEY,
            ScopeInterface::SCOPE_STORE,
            $store
        ) ?? '');
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function getSecretKey($store = null)
    {
        return trim($this->scopeConfig->getValue(
            self::XML_PATH_SETUP_SECRET_KEY,
            ScopeInterface::SCOPE_STORE,
            $store
        )?? '');
    }

    /**
     *
     * @param null $store
     * @return mixed
     */
    public function getEnableSerialization($store = null){
        
        return trim($this->scopeConfig->getValue(
            self::XML_PATH_CATALOG_FEED_STREAMING_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $store
        )?? '');
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function getApiKey($store = null)
    {
        return trim($this->scopeConfig->getValue(
            self::XML_PATH_SETUP_API_KEY,
            ScopeInterface::SCOPE_STORE,
            $store
        )?? '');
    }

    /**
     * @param null $store
     * @return string
     */
    public function getFullFeedApiEndpoint($store = null)
    {
        return trim($this->scopeConfig->getValue(
            self::XML_PATH_FULL_FEED_API_ENDPOINT,
            ScopeInterface::SCOPE_STORE,
            $store
        )?? '');
    }

    /**
     * @param null $store
     * @return string
     */
    public function getIncrementalFeedApiEndpoint($store = null)
    {
        return trim($this->scopeConfig->getValue(
            self::XML_PATH_INCREMENTAL_FEED_API_ENDPOINT,
            ScopeInterface::SCOPE_STORE,
            $store
        )?? '');
    }

    /**
     * @param null $store
     * @return string
     */
    public function getFullUploadedStatusApiEndpoint($store = null)
    {
        return trim($this->scopeConfig->getValue(
            self::XML_PATH_FULL_UPLOADED_STATUS,
            ScopeInterface::SCOPE_STORE,
            $store
        )?? '');
    }

    /**
     * @param null $store
     * @return string
     */
    public function getIncrementalUploadedStatusApiEndpoint($store = null)
    {
        return trim($this->scopeConfig->getValue(
            self::XML_PATH_INCREMENTAL_UPLOADED_STATUS,
            ScopeInterface::SCOPE_STORE,
            $store
        )?? '');
    }

    /**
     * @param null $store
     * @return string
     */
    public function getUploadedSizeApiEndpoint($store = null)
    {
        return trim($this->scopeConfig->getValue(
            self::XML_PATH_UPLOADED_SIZE,
            ScopeInterface::SCOPE_STORE,
            $store
        )?? '');
    }

    /**
     * @param null $store
     * @return bool
     */
    public function isAuthorizationCredentialsSetup($store = null)
    {
        return (bool) ($this->getSiteKey($store) && $this->getSecretKey($store) && $this->getApiKey($store));
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function getNumberOfVariantToExport($store = null)
    {
        return trim($this->scopeConfig->getValue(
            self::XML_PATH_NUMBER_OF_VARIANTS,
            ScopeInterface::SCOPE_STORE,
            $store
        )?? 0);
    }
    

    /**
     * Retrieve all product types supported by Unbxd service
     *
     * @param null $store
     * @return array
     */
    public function getAvailableProductTypes($store = null)
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_CATALOG_AVAILABLE_PRODUCT_TYPES,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        $allProductTypes = array_keys($this->productTypes->toArray());
        array_shift($allProductTypes); // remove 'all' key from types
        if ($value) {
            $types = explode(',', $value);
            if (!empty($types)) {
                if (in_array(ProductTypes::ALL_KEY, $types)) {
                    $types = $allProductTypes;
                }
            }
        } else {
            $types = $allProductTypes;
        }

        return $types;
    }

    /**
     * @param null $store
     * @return array|FilterAttributeInterface[]
     */
    public function getFilterAttributes($store = null)
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_CATALOG_EXCLUDE_PRODUCTS_FILTER_ATTRIBUTES,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        $attributes = [];
        if ($value) {
            $attributes = explode(',', $value);
            if (!empty($attributes)) {
                if (in_array(FilterAttribute::DON_NOT_EXCLUDE_KEY, $attributes)) {
                    return [];
                }

                $result = [];
                foreach ($attributes as $attributeCode) {
                    /** @var FilterAttributeInterface $attribute */
                    $attribute = $this->filterAttributeProvider->getAttribute($attributeCode);
                    if ($attribute) {
                        $result[] = $attribute;
                    }
                }
                return $result;
            }
        }
        return $attributes;
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function checkModuleVersionEnabled($scopeCode = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_CATALOG_VERSION_CHECK,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            $scopeCode
        );
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function resizeImageOnDemand($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_IMAGES_RESIZE_IMAGE_WHEN_NOT_FOUND,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }
    
    

    /**
     * @param null $store
     * @return mixed
     */
    public function useCachedProductImages($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_IMAGES_USE_CACHED_PRODUCT_IMAGES,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function useCategoryID($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_USE_CATEGORY_ID,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function fetchFromCategoryTable($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_FETCH_FROM_CATEGORY_TABLES,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    

    /**
     * @param null $store
     * @return mixed
     */
    public function retainInActiveCategories($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_RETAIN_INACTIVE_CATEGORY,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function retainRootCategory($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_RETAIN_ROOT_CATEGORY,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function removePubDirectoryFromUrl($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_IMAGES_REMOVE_PUB_MEDIA_URL,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param $type
     * @param null $store
     * @return mixed|null
     */
    public function getImageByType($type, $store = null)
    {
        if ($this->useCachedProductImages($store)) {
            switch ($type) {
                case 'image':
                    $path = self::XML_PATH_IMAGES_BASE_IMAGE_ID;
                    break;
                case 'small_image':
                    $path = self::XML_PATH_IMAGES_SMALL_IMAGE_ID;
                    break;
                case 'thumbnail':
                    $path = self::XML_PATH_IMAGES_THUMBNAIL_ID;
                    break;
                case 'swatch_image':
                    $path = self::XML_PATH_IMAGES_SWATCH_IMAGE_ID;
                    break;
            }
            return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $store);
        }
        return null;
    }

    /**
     * @param null $store
     * @return int
     */
    public function getMaxNumberOfAttempts($store = null)
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_CATALOG_MAX_NUMBER_OF_ATTEMPTS,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function isMultiPartUploadEnabled($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_CATALOG_MULTI_PART_UPLOAD_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function getBatchSize($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_CATALOG_BATCH_SIZE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }
    

    /**
     * @param null $store
     * @return mixed
     */
    public function getMultiPartBatchSize($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_CATALOG_MULTI_PART_BATCH_SIZE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }
    

    /**
     * @param null $store
     * @return mixed
     */
    public function isPartialIncrementalEnabled($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_CATALOG_INDEXING_PARTIAL_INCREMENTAL_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function isIndexingQueueEnabled($store = null)
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_CATALOG_INDEXING_QUEUE_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function getIndexingQueueArchivalTime($store = null)
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_INDEXING_QUEUE_ARCHIVAL_INMINUTES,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function isCleanupFileOnCompletion($store = null)
    {
        
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_FEED_FILE_CLEANUP,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

     /**
     * @param null $store
     * @return mixed
     */
    public function getFeedViewArchivalTime($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_FEED_VIEW_ARCHIVAL_INMINUTES,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return array|null
     */
    public function getDataFieldsMapping($store = null)
    {
        if (null === $this->dataFieldsMapping) {
            $mappingSettings = $this->scopeConfig->getValue(
                self::XML_PATH_CATALOG_DATA_FIELDS_MAPPING_SETTINGS,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $store
            );

            $result = [];
            if (is_string($mappingSettings) && strlen($mappingSettings) > 0) {
                $mappingSettings = $this->serializer->unserialize($mappingSettings);
                if (is_array($mappingSettings) && count($mappingSettings) > 0) {
                    foreach ($mappingSettings as $data) {
                        $mapping = new \Magento\Framework\DataObject();
                        $mapping->setData($data);

                        if (
                            $mapping->getIsEnabled()
                            && $mapping->getUnbxdField()
                            && $mapping->getProductAttribute()
                        ) {
                            $result[$mapping->getUnbxdField()] = $mapping->getProductAttribute();
                        }
                    }
                    $this->dataFieldsMapping = $result;
                }
            }
        }

        return $this->dataFieldsMapping;
    }

    /**
     * @param null $store
     * @return bool
     */
    public function isGeneralCronEnabled($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_CATALOG_CRON_GENERAL_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return bool|null
     */
    public function getGeneralCronType($store = null)
    {
        if ($this->isGeneralCronEnabled($store)) {
            return $this->scopeConfig->getValue(
                self::XML_PATH_CATALOG_CRON_GENERAL_TYPE,
                ScopeInterface::SCOPE_STORE,
                $store
            );
        }

        return null;
    }

    /**
     * @param null $store
     * @return bool|null
     */
    public function getGeneralCronSchedule($store = null)
    {
        if ($this->isGeneralCronEnabled($store) && $this->getGeneralCronType($store)) {
            return $this->scopeConfig->getValue(
                CronGeneral::CRON_GENERAL_STRING_PATH,
                ScopeInterface::SCOPE_STORE,
                $store
            );
        }

        return null;
    }

    /**
     * Check whether general cron job is configured or not
     *
     * @param null $store
     * @return bool
     */
    public function isGeneralCronConfigured($store = null)
    {
        return (bool) ($this->isGeneralCronEnabled($store) && $this->getGeneralCronSchedule($store));
    }

    /**
     * @param null $store
     * @return bool
     */
    public function isFullFeedCronEnabled($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_CATALOG_CRON_FULL_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return bool|null
     */
    public function getFullFeedCronType($store = null)
    {
        if ($this->isFullFeedCronEnabled($store)) {
            return $this->scopeConfig->getValue(
                self::XML_PATH_CATALOG_CRON_FULL_TYPE,
                ScopeInterface::SCOPE_STORE,
                $store
            );
        }

        return null;
    }

    /**
     * @param null $store
     * @return bool|null
     */
    public function getFullFeedCronSchedule($store = null)
    {
        if ($this->isFullFeedCronEnabled($store) && $this->getFullFeedCronType($store)) {
            return $this->scopeConfig->getValue(
                CronFullFeed::CRON_FULL_STRING_PATH,
                ScopeInterface::SCOPE_STORE,
                $store
            );
        }

        return null;
    }

    /**
     * Check whether full feed cron job is configured or not
     *
     * @param null $store
     * @return bool
     */
    public function isFullFeedCronConfigured($store = null)
    {
        return (bool) ($this->isFullFeedCronEnabled($store) && $this->getFullFeedCronSchedule($store));
    }

    /**
     * Check whether manual synchronization enabled or not
     *
     * @param null $store
     * @return bool
     */
    public function isManualSynchronizationEnabled($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_CATALOG_MANUAL_SYNCHRONIZATION_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param bool $dateTime
     * @return false|string
     * @throws \Exception
     */
    public function formatDateTime($dateTime = false)
    {
        if (!$dateTime) {
            $dateTime = time();
        }

        $dateTimeObject = date_create($dateTime);
        if (!$dateTimeObject instanceof \DateTime) {
            $dateTimeObject = new \DateTime();
        }

        return date_format($dateTimeObject, 'Y-m-d\TH:i:s\Z');
    }
}