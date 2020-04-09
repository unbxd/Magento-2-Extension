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

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Config\DataInterface as ConfigDataInterface;

/**
 * Class Config
 * @package Unbxd\ProductFeed\Model\Feed
 */
class Config
{
    /**
     * Feed API Synchronization default endpoints (will be used if related endpoints are not specified in config fields)
     */
    const FEED_FULL_API_ENDPOINT_DEFAULT = 'http://feed.unbxd.io/api/%s/upload/catalog/full';
    const FEED_INCREMENTAL_API_ENDPOINT_DEFAULT = 'http://feed.unbxd.io/api/%s/upload/catalog/delta';
    const FEED_FULL_UPLOADED_STATUS_API_ENDPOINT_DEFAULT = 'http://feed.unbxd.io/api/%s/catalog/%s/status';
    const FEED_INCREMENTAL_UPLOADED_STATUS_API_ENDPOINT_DEFAULT = 'http://feed.unbxd.io/api/%s/catalog/delta/%s/status';
    const FEED_UPLOADED_SIZE_API_ENDPOINT_DEFAULT = 'http://feed.unbxd.io/api/%s/catalog/size';

    /**
     * Feed messages who are responsible for specific operation
     */
    const FEED_MESSAGE_BY_RESPONSE_TYPE_RUNNING =
        'Product feed is currently synchronizing with the Unbxd service.';
    const FEED_MESSAGE_BY_RESPONSE_TYPE_INDEXING =
        'Product feed has been successfully uploaded.<br/> Processing by Unbxd service.';
    const FEED_MESSAGE_BY_RESPONSE_TYPE_COMPLETE =
        'Product feed has been successfully processed by Unbxd service.';
    const FEED_MESSAGE_BY_RESPONSE_TYPE_ERROR =
        'Synchronization failed for store(s) with ID(s): %s.<br/> See feed view logs for additional information';
    const FEED_MESSAGE_UPLOAD_SIZE = 'Total Uploaded Feed Size - %s';

    /**
     * Feed types:
     *
     *  full - full catalog product synchronization
     *  incremental - separate product(s) synchronization
     *  full_uploaded_status - feed upload status for a given upload id
     *  incremental_uploaded_status - incremental feed upload status for a given upload id
     *  uploaded_size - the number of records present.
     */
    const FEED_TYPE_FULL = 'full';
    const FEED_TYPE_INCREMENTAL = 'incremental';
    const FEED_TYPE_FULL_UPLOADED_STATUS = 'full_uploaded_status';
    const FEED_TYPE_INCREMENTAL_UPLOADED_STATUS = 'incremental_uploaded_status';
    const FEED_TYPE_UPLOADED_SIZE = 'uploaded_size';
    const FEED_TYPE_ANALYTICS = 'analytics';

    /**
     * Flag to check whether or not include catalog fields to feed data
     */
    const INCLUDE_CATALOG = true;

    /**
     * Flag to check whether or not include schema fields to feed data
     */
    const INCLUDE_SCHEMA = true;

    /**
     * Default schema auto suggest field value
     */
    const DEFAULT_SCHEMA_AUTO_SUGGEST_FIELD_VALUE = false;

    /**
     * Index fields related to child products (variants)
     */
    const CHILD_PRODUCT_SKUS_FIELD_KEY = 'children_sku';
    const CHILD_PRODUCT_IDS_FIELD_KEY = 'children_ids';
    const CHILD_PRODUCT_ATTRIBUTES_FIELD_KEY = 'children_attributes';
    const CHILD_PRODUCT_CONFIGURABLE_ATTRIBUTES_FIELD_KEY = 'configurable_attributes';

    /**
     * Field key for feed data
     */
    const FEED_FIELD_KEY = 'feed';

    /**
     * Field key for catalog data
     */
    const CATALOG_FIELD_KEY = 'catalog';

    /**
     * Field key for catalog items
     */
    const CATALOG_ITEMS_FIELD_KEY = 'items';

    /**
     * Field key for schema fields
     */
    const SCHEMA_FIELD_KEY = 'schema';

    /**
     * Child products (variants) field key
     */
    const CHILD_PRODUCTS_FIELD_KEY = 'variants';

    /**
     * Child product (variant) field prefix
     */
    const CHILD_PRODUCT_FIELD_PREFIX = 'v';

    /**
     * Child product (variant) field unique ID
     */
    const CHILD_PRODUCT_FIELD_VARIANT_ID = 'variant_id';

    /**
     * Parent ID key to detect whether or not child product related to any parent product(s)
     */
    const PARENT_ID_KEY = 'parent_id';

    /**
     * Helper key to detect whether or not current item fields are prepared
     */
    const PREPARED_FIELDS_KEY = 'prepared';

    /**
     * Default batch size for prepare feed data
     */
    const DEFAULT_BATCH_SIZE_PREPARE_FEED_DATA = 1000;

    /**
     * Default batch size for write feed data
     */
    const DEFAULT_BATCH_SIZE_WRITE_FEED_DATA = 1000;

    /**
     * Check whether Unbxd service support post method curl file create param
     */
    const CURL_FILE_CREATE_POST_PARAM_SUPPORT = true;

    /**
     * Flag to detect if need to send additional API call to check uploaded feed status
     */
    const VALIDATE_STATUS_FOR_UPLOADED_FEED = true;

    /**
     * Flag to detect if need to send additional API call to retrieve uploaded feed size
     */
    const RETRIEVE_SIZE_FOR_UPLOADED_FEED = false;

    /**
     * Processing status for upload feed size
     */
    const FEED_SIZE_CALCULATION_STATUS = 'Calculation';

    /**
     * Feed operation types (e.g. add new product, update product data, delete product)
     */
    const OPERATION_TYPE_ADD       = 'add';
    const OPERATION_TYPE_UPDATE    = 'update';
    const OPERATION_TYPE_DELETE    = 'delete';
    const OPERATION_TYPE_FULL      = 'full';

    /**
     * Standard field types declaration.
     */
    const FIELD_TYPE_BOOL       = 'bool';
    const FIELD_TYPE_TEXT       = 'text';
    const FIELD_TYPE_LONGTEXT   = 'longText';
    const FIELD_TYPE_LINK       = 'link';
    const FIELD_TYPE_NUMBER     = 'number';
    const FIELD_TYPE_DECIMAL    = 'decimal';
    const FIELD_TYPE_DATE       = 'date';

    /**
     * Default fields declaration use for map
     */
    const FIELD_KEY_ENTITY_ID           = 'entity_id';
    const FIELD_KEY_PRODUCT_NAME        = 'name';
    const FIELD_KEY_IMAGE_PATH          = 'image';
    const FIELD_KEY_SMALL_IMAGE_PATH    = 'small_image';
    const FIELD_KEY_THUMBNAIL_PATH      = 'thumbnail';
    const FIELD_KEY_SWATCH_IMAGE_PATH   = 'swatch_image';
    const FIELD_KEY_PRODUCT_URL_KEY     = 'url_key';
    const FIELD_KEY_STOCK_STATUS        = 'quantity_and_stock_status';
    const FIELD_KEY_CATEGORY_DATA       = 'category';
    const FIELD_KEY_VISIBILITY          = ProductInterface::VISIBILITY;

    /**
     * Specific fields declaration
     */
    const SPECIFIC_FIELD_KEY_UNIQUE_ID          = 'unique_id';
    const SPECIFIC_FIELD_KEY_TITLE              = 'title';
    const SPECIFIC_FIELD_KEY_IMAGE_URL          = 'image_url';
    const SPECIFIC_FIELD_KEY_PRODUCT_URL        = 'product_url';
    const SPECIFIC_FIELD_KEY_AVAILABILITY       = 'availability';
    const SPECIFIC_FIELD_KEY_CATEGORY_PATH_ID   = 'category_path_id';

    /**
     * @var ConfigDataInterface
     */
    private $defaultDataFieldsMappingStorage;

    /**
     * Config constructor.
     * @param ConfigDataInterface $defaultDataFieldsMappingStorage
     */
    public function __construct(
        ConfigDataInterface $defaultDataFieldsMappingStorage
    ) {
        $this->defaultDataFieldsMappingStorage = $defaultDataFieldsMappingStorage;
    }

    /**
     * Get list of default data mapping fields using for product feed
     *
     * @return array
     */
    public function getDefaultDataFieldsMappingStorage()
    {
        return $this->defaultDataFieldsMappingStorage->get('fields', []);
    }

    /**
     * Default data fields mapping
     *
     * @return array
     * @deprecated
     */
    public function getDefaultDataFieldsMapping()
    {
        return [
            self::FIELD_KEY_ENTITY_ID => self::SPECIFIC_FIELD_KEY_UNIQUE_ID,
            self::FIELD_KEY_PRODUCT_NAME => self::SPECIFIC_FIELD_KEY_TITLE,
            self::FIELD_KEY_IMAGE_PATH => self::SPECIFIC_FIELD_KEY_IMAGE_URL,
            self::FIELD_KEY_PRODUCT_URL_KEY => self::SPECIFIC_FIELD_KEY_PRODUCT_URL,
            self::FIELD_KEY_STOCK_STATUS => self::SPECIFIC_FIELD_KEY_AVAILABILITY,
            self::FIELD_KEY_CATEGORY_DATA => self::SPECIFIC_FIELD_KEY_CATEGORY_PATH_ID,
            self::FIELD_KEY_VISIBILITY => self::FIELD_KEY_VISIBILITY // use for retrieve label instead of ID
        ];
    }

    /**
     * Available feed operation types
     *
     * @return array
     */
    public function getAvailableOperationTypes()
    {
        return [
            self::OPERATION_TYPE_FULL => __('Full'),
            self::OPERATION_TYPE_ADD => __('Add'),
            self::OPERATION_TYPE_UPDATE => __('Update'),
            self::OPERATION_TYPE_DELETE => __('Delete')
        ];
    }

    /**
     * Parent product fields which contain children related information
     *
     * @return array
     */
    public function getParentChildrenRelatedFields()
    {
        return [
            self::CHILD_PRODUCT_SKUS_FIELD_KEY,
            self::CHILD_PRODUCT_IDS_FIELD_KEY,
            self::CHILD_PRODUCT_ATTRIBUTES_FIELD_KEY,
            self::CHILD_PRODUCT_CONFIGURABLE_ATTRIBUTES_FIELD_KEY
        ];
    }

    /**
     * Additional fields which must be excluded from feed content.
     *
     * @return array
     */
    public function getExcludedFields()
    {
        return [
            'indexed_attributes'
        ];
    }

    /**
     * Helper full key to detect whether or not current item fields are prepared
     *
     * @return string
     */
    public static function getPreparedKey()
    {
        return sprintf('_%s_', self::PREPARED_FIELDS_KEY);
    }
}