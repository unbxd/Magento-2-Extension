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
use Magento\Catalog\Helper\Product as HelperProduct;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Unbxd\ProductFeed\Helper\AttributeHelper;
use Unbxd\ProductFeed\Helper\Data as HelperData;
use Unbxd\ProductFeed\Helper\Feed as FeedHelper;
use Unbxd\ProductFeed\Helper\ProductHelper;
use Magento\Framework\UrlInterface;
use Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator;
use Unbxd\ProductFeed\Model\Feed\DataHandler\Category as CategoryDataHandler;
use Unbxd\ProductFeed\Model\Feed\DataHandler\Image as ImageDataHandler;
use Unbxd\ProductFeed\Model\Feed\Config as FeedConfig;
use Unbxd\ProductFeed\Logger\LoggerInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Api\SimpleDataObjectConverter;

/**
 * Class DataHandler
 *
 * Supported events:
 *   - unbxd_productfeed_prepare_data_before
 *   - unbxd_productfeed_prepare_data_after
 *
 * @package Unbxd\ProductFeed\Model\Feed
 */
class DataHandler
{
    /**
     * @var LoggerInterface
     */
    private $logger;

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
     * @var StoreManagerInterface
     */
    private $storeManager;

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
     * @var CategoryDataHandler
     */
    private $categoryDataHandler;

    /**
     * @var ImageDataHandler
     */
    private $imageDataHandler;

    /**
     * @var FeedConfig
     */
    private $feedConfig;

    /**
     * @var UrlInterface
     */
    private $frontendUrlBuilder;

    /**
     * Indexed fields with properties
     *
     * @var array
     */
    private $indexedFields = [];

    /**
     * used to hold the snakecase conversion to actual key
     *
     * @var array
     */
    private $keyMap = [];

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
     * Local cache for images that have been processed
     *
     * @var array
     */
    protected $processedImages = [];

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
     * Local cache for children which can also be sold individually and data is prepared
     *
     * @var array
     */
    private $relatedEntityPreparedDataList = [];

    /**
     * Local cache for data fields mapping
     *
     * @var array
     */
    private $dataFieldsMapping = [];

    /**
     * @var string|null
     */
    private $loggerType = null;

    /**
     * DataHandler constructor.
     * @param EventManager $eventManager
     * @param StoreManagerInterface $storeManager
     * @param HelperData $helperData
     * @param FeedHelper $feedHelper
     * @param ProductHelper $productHelper
     * @param CategoryDataHandler $categoryDataHandler
     * @param ImageDataHandler $imageDataHandler
     * @param Config $feedConfig
     * @param UrlInterface $frontendUrlBuilder
     * @param $loggerType
     * @param LoggerInterface $logger
     */
    public function __construct(
        EventManager $eventManager,
        StoreManagerInterface $storeManager,
        HelperData $helperData,
        FeedHelper $feedHelper,
        ProductHelper $productHelper,
        CategoryDataHandler $categoryDataHandler,
        ImageDataHandler $imageDataHandler,
        FeedConfig $feedConfig,
        UrlInterface $frontendUrlBuilder,
        LoggerInterface $logger,
        $loggerType = null
    ) {
        $this->eventManager = $eventManager;
        $this->storeManager = $storeManager;
        $this->helperData = $helperData;
        $this->feedHelper = $feedHelper;
        $this->productHelper = $productHelper;
        $this->categoryDataHandler = $categoryDataHandler;
        $this->imageDataHandler = $imageDataHandler;
        $this->feedConfig = $feedConfig;
        $this->frontendUrlBuilder = $frontendUrlBuilder;
        $this->logger = $logger->create($loggerType);
        $this->loggerType = $loggerType;
    }

    /**
     * @param array $fields
     * @return $this
     */
    private function setIndexedFields(array $fields = [])
    {
        $this->indexedFields = $fields;
        return $this;
    }

    /**
     * @return array
     */
    private function getIndexedFields()
    {
        return $this->indexedFields;
    }

    /**
     * @param $data
     * @return $this
     */
    private function setFullFeed($data)
    {
        $this->fullFeed = $data;
        return $this;
    }

    /**
     * @return array
     */
    public function getFullFeed()
    {
        return $this->fullFeed;
    }

    /**
     * @param array $index
     * @param null $store
     * @return array
     * @throws NoSuchEntityException
     */
    public function initFeed(array $index, $store = null)
    {
        $this->prepareData($index, $store);
        $this->buildFeed();
        return $this->getFullFeed();
    }

    /**
     * Prepare index data for feed operations
     *
     * @param array $index
     * @param null $store
     * @return $this
     * @throws NoSuchEntityException
     */
    public function prepareData(array $index, $store = null)
    {
        $this->logger->info('Prepare feed content based on index data.');
        $this->logger->info('Dispatch event: ' . $this->eventPrefix . '_prepare_data_before.');
        $this->eventManager->dispatch(
            $this->eventPrefix . '_prepare_data_before',
            ['index' => $index, 'feed_manager' => $this]
        );

        $indexedFields = array_key_exists('fields', $index) ? $index['fields'] : [];
        // must be set before build catalog data for validate field values according to fields properties
        $this->setIndexedFields($indexedFields);
        unset($index['fields']);
        $this->buildCatalogData($index, $store);
        $this->buildSchemaFields();
        
        $this->logger->info('Dispatch event: ' . $this->eventPrefix . '_prepare_data_after.');
        $this->eventManager->dispatch(
            $this->eventPrefix . '_prepare_data_after',
            ['index' => $index, 'feed_manager' => $this]
        );

        return $this;
    }

    /**
     * @param $key
     * @return $this
     */
    private function setChildrenSchemaFields($key)
    {
        if (!in_array($key, $this->childrenSchemaFields)) {
            $this->childrenSchemaFields[] = $key;
            if ($key == Config::SPECIFIC_FIELD_KEY_UNIQUE_ID) {
                $this->childrenSchemaFields[] = Config::CHILD_PRODUCT_FIELD_VARIANT_ID;
            }
        }

        return $this;
    }

    /**
     * @return array
     */
    private function getChildrenSchemaFields()
    {
        return array_unique($this->childrenSchemaFields);
    }

    /**
     * @return $this
     */
    private function buildSchemaFields()
    {
        $indexedFields = $this->getIndexedFields();
        if (empty($indexedFields)) {
            $this->logger->info('Can\'t prepare schema fields. Index data is empty.');
            return $this;
        }

        $additionalFields = $this->feedConfig->getAdditionalFields();
        $dataFieldsMapping = $this->buildDataFieldsMapping();
        $urlAttributes = $this->feedConfig->getUrlAttributes();
        foreach ($indexedFields as $fieldCode => &$fieldData) {
            // process excluded fields
            if (in_array($fieldCode, $additionalFields)) {
                unset($indexedFields[$fieldCode]);
            }
            // process mapped fields
            if (array_key_exists($fieldCode, $dataFieldsMapping)) {
                // include mapped field, leave the field from which it was mapped, as it can also be transferred
                $mappedFieldKey = $dataFieldsMapping[$fieldCode];
                $indexedFields[$mappedFieldKey] = array_replace($fieldData, ['fieldName' => $mappedFieldKey]);
            }
            if (in_array($fieldData['fieldName'], $urlAttributes)) {
                $fieldData['dataType']='link';
            }
            // convert to needed format
            if(!strpos($fieldData['fieldName'],"*")){
            $fieldData['fieldName'] = SimpleDataObjectConverter::snakeCaseToCamelCase($fieldData['fieldName']);
            }
        }
        // process child fields
        $this->appendChildFieldsToSchema($indexedFields);

        $this->schema = [
            Config::SCHEMA_FIELD_KEY => array_values($indexedFields)
        ];

        return $this;
    }

    /**
     * @param array $fields
     * @return $this
     */
    private function appendChildFieldsToSchema(array &$fields)
    {
        // try to add children fields to schema
        foreach ($this->getChildrenSchemaFields() as $childField) {
            // add only fields that already exist in schema fields
            if (array_key_exists($childField, $fields)) {
                $childKey = FeedConfig::CHILD_PRODUCT_FIELD_PREFIX.ucfirst(SimpleDataObjectConverter::snakeCaseToCamelCase($childField));
                
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

        return $this;
    }

    /**
     * @param array $index
     * @param null $store
     * @return $this
     * @throws NoSuchEntityException
     */
    private function buildCatalogData(array $index, $store = null)
    {
        if (empty($index)) {
            $this->logger->error('Can\'t prepare catalog data. Index data is empty.');
            return $this;
        }

        foreach ($index as $productId => &$data) {
            try {
                // schema fields has key 'fields', do only for products
                if (is_int($productId)) {
                    // append child data to parent
                    if (
                        isset($data[Config::CHILD_PRODUCT_IDS_FIELD_KEY])
                        && !empty($data[Config::CHILD_PRODUCT_IDS_FIELD_KEY])
                    ) {
                        $currentChildIds = $data[Config::CHILD_PRODUCT_IDS_FIELD_KEY];
                        $this->appendChildDataToParent($index, $data, $currentChildIds, $store);
                    } else {
                        // if product doesn't have children - add empty variants data
                        $data[Config::CHILD_PRODUCTS_FIELD_KEY] = [];
                    }

                    // check if product related to parent product (variant product),
                    // if so - do not add child to feed catalog data, just add it like variant product
                    if (isset($data[Config::PARENT_ID_KEY])) {
                        unset($data[Config::PARENT_ID_KEY]);
                        if (!isset($data[Config::FIELD_KEY_VISIBILITY]) || empty($data[Config::FIELD_KEY_VISIBILITY]) || (is_array($data[Config::FIELD_KEY_VISIBILITY]) ? $data[Config::FIELD_KEY_VISIBILITY][0] : $data[Config::FIELD_KEY_VISIBILITY]) == "Not Visible Individually" || $this->getVisibilityTypeLabel($data[Config::FIELD_KEY_VISIBILITY][0]) == "Not Visible Individually") {
                            continue;
                        }
                        $this->relatedEntityPreparedDataList[]=$data['entity_id'];

                    }else if (isset($data[Config::FIELD_KEY_VISIBILITY]) && !empty($data[Config::FIELD_KEY_VISIBILITY]) && ((is_array($data[Config::FIELD_KEY_VISIBILITY]) ? $data[Config::FIELD_KEY_VISIBILITY][0] : $data[Config::FIELD_KEY_VISIBILITY]) == "Not Visible Individually" || $this->getVisibilityTypeLabel($data[Config::FIELD_KEY_VISIBILITY][0]) == "Not Visible Individually")) {
                        continue;
                    }
                    // prepare data fields for needed requirements
                    if (!isset($data[Config::getPreparedKey()])) {
                        $this->prepareFields($data, $store);
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
                        $data = [$key => strval($productId)];
                    }
                    $this->catalog[$operationKey][Config::CATALOG_ITEMS_FIELD_KEY][] = $data;
                }
            } catch (\Exception $e) {
                $this->logger->error("Encountered exception while processing product -" . $data["sku"]." with error ".$e->getMessage()." -stack-".$e->getTraceAsString());
            }
        }

        return $this;
    }

    /**
     * Build feed content to needed format based on prepared index data
     *
     * @return $this|bool
     */
    private function buildFeed()
    {
        $this->logger->info('Build feed content.');

        // check only catalog data, we don't need to check schema fields
        // in case if 'delete' operation will be performing, scheme fields are not required
        if (empty($this->catalog)) {
            $this->logger->info('Can\'t build feed content. Prepared data is empty.');
            return false;
        }

        $fullFeed = [];
        if (!empty($this->schema) && Config::INCLUDE_SCHEMA) {
            $fullFeed = array_merge($fullFeed, $this->schema);
        }
        if (!empty($this->catalog) && Config::INCLUDE_CATALOG) {
            $fullFeed = array_merge($fullFeed, $this->catalog);
        }
        if (!empty($fullFeed)) {
            $fullFeed = [
                FeedConfig::FEED_FIELD_KEY => [
                    FeedConfig::CATALOG_FIELD_KEY => $fullFeed
                ]
            ];
            $this->setFullFeed($fullFeed);
        }

        return $this;
    }

    /**
     * @param array $data
     * @param null $store
     * @return $this
     * @throws NoSuchEntityException
     */

    private function prepareFields(array &$data, $store = null)
    {
        $this->applyWebsiteStoreFields($data, $store)
            ->applyDataFieldsMapping($data, $store)
            ->applyMediaAttributes($data, $store)
            ->buildFields($data);

        // mark to prevent not prepared data
        $data[Config::getPreparedKey()] = true;

        return $this;
    }


    /**
     * Check and add 'website_id' and 'store_id' fields to formed feed
     * (in case if they missing in some reason)
     *
     * @param array $data
     * @param null $store
     * @return $this
     * @throws NoSuchEntityException
     */
    private function applyWebsiteStoreFields(array &$data, $store = null)
    {
        $storeId = $store ? $store : $this->getStore()->getId();
        $fields = [
            Store::STORE_ID => $storeId,
            'website_id' => $this->getWebsite($storeId)->getId()
        ];

        foreach ($fields as $key => $value) {
            if (!array_key_exists($key, $data)) {
                $data[$key] = (int) $value;
            }
        }

        return $this;
    }

    /**
     * Apply data fields mapping
     *
     * @param array $data
     * @param null $store
     * @return $this
     * @throws NoSuchEntityException
     */
    private function applyDataFieldsMapping(array &$data, $store = null)
    {
        $dataFieldsMapping = $this->buildDataFieldsMapping();
        $productId = $data["entity_id"];
        foreach ($dataFieldsMapping as $productAttribute => $unbxdField) {
            // apply only for fields that are present in the formed feed
            if (!array_key_exists($productAttribute, $data)) {
                continue;
            }

            if (is_array($data[$productAttribute])) {
                // retrieve only first required value
                $value = $data[$productAttribute][0];
            } else {
                $value = (string) $data[$productAttribute];
            }

            // build correct value for provided specific fields
            switch ($productAttribute) {
                case Config::FIELD_KEY_PRODUCT_URL_KEY:
                    $storeId = isset($data[Store::STORE_ID]) ? $data[Store::STORE_ID] : $this->getStore()->getId();
                    $productUrl = $this->buildProductUrl($value, $storeId);
                    if ($productUrl) {
                        $data[$unbxdField] = $productUrl;
                        unset($data[$productAttribute]);
                    }
                    break;
                case Config::FIELD_KEY_IMAGE_PATH:
                case Config::FIELD_KEY_SMALL_IMAGE_PATH:
                case Config::FIELD_KEY_THUMBNAIL_PATH:
                case Config::FIELD_KEY_SWATCH_IMAGE_PATH:

                    $imageUrl = $this->imageDataHandler->getImageUrl($productId,$value, $productAttribute,  $store);
                    if ($imageUrl) {
                        $data[$unbxdField] = $imageUrl;
                        unset($data[$productAttribute]);
                        $this->setProcessedImages($productAttribute);
                    }
                    break;
                case Config::FIELD_KEY_CATEGORY_DATA:
                    $categoryData = $this->categoryDataHandler->buildCategoryList($data[Config::FIELD_KEY_CATEGORY_DATA],$store,$data["entity_id"]);
                    if (!empty($categoryData)) {
                        $data[$unbxdField] = $categoryData;
                    }
                    // to prevent send not valid category data, if category list in required format was not formed
                    unset($data[$productAttribute]);
                    break;
                case Config::FIELD_KEY_VISIBILITY:
                    // retrieve visibility label instead of ID
                    $data[$productAttribute] = $this->getVisibilityTypeLabel($value);
                    break;
                default:
                    $data[$unbxdField] = (string) $value;
                    // don't remove SKU field from feed data, even if the field is mapped
                    if ($productAttribute != ProductInterface::SKU && $productAttribute != "entity_id") {
                        unset($data[$productAttribute]);
                    }
                    break;
            }
        }
        return $this;
    }



    /**
     * @param $imageType
     * @return $this
     */
    private function setProcessedImages($imageType)
    {
        if (!in_array($imageType, $this->processedImages)) {
            $this->processedImages[] = $imageType;
        }

        return $this;
    }

    /**
     * @return array
     */
    private function getProcessedImages()
    {
        return array_values(array_unique($this->processedImages));
    }

    /**
     * @return $this
     */
    private function resetProcessedImages()
    {
        $this->processedImages = [];
        return $this;
    }

    /**
     * @param array $data
     * @param null $store
     * @return $this
     */
    private function applyMediaAttributes(array &$data, $store = null)
    {
        $productId = $data["entity_id"];
        foreach (ImageDataHandler::getMediaAttributes() as $attribute) {
            if (isset($data[$attribute]) && !in_array($attribute, $this->getProcessedImages())) {
                $value = is_array($data[$attribute]) ? $data[$attribute][0] : $data[$attribute];
                $data[$attribute] = $this->imageDataHandler->getImageUrl($productId,(string) $value, $attribute, $store);
            }
        }
        // clear processed images for current product
        $this->resetProcessedImages();
        return $this;
    }

    /**
     * Return custom mapping (ex. visibility, to retrieve label instead of ID)
     *
     * @return array
     */
    private function getCustomDataFieldsMapping()
    {
        return [
            FeedConfig::FIELD_KEY_VISIBILITY => FeedConfig::FIELD_KEY_VISIBILITY
        ];
    }

    /**
     * Build data fields mapping between Unbxd service and Magento
     *
     * @return array
     */
    private function buildDataFieldsMapping()
    {
        if (empty($this->dataFieldsMapping)) {
            $dataFieldsMapping = $this->feedConfig->getDefaultDataFieldsMappingStorage();
            $customDataFieldsMapping = $this->feedHelper->getDataFieldsMapping();
            if (!$customDataFieldsMapping) {
                // merge with custom data fields mapping
                $dataFieldsMapping = array_merge($dataFieldsMapping, $this->getCustomDataFieldsMapping());
                $this->dataFieldsMapping = $dataFieldsMapping;
                return $this->dataFieldsMapping;
            }

            foreach ($customDataFieldsMapping as $unbxField => $productAttribute) {
                // try to replace default mapping, only in case if custom mapping was applied
                if (in_array($unbxField, $dataFieldsMapping)) {
                    $checkedKey = array_search($unbxField, $dataFieldsMapping);
                    if ($checkedKey != $productAttribute) {
                        $dataFieldsMapping[$productAttribute] = $unbxField;
                        unset($dataFieldsMapping[$checkedKey]);
                    }
                } else {
                    // extend default mapping
                    $dataFieldsMapping[$unbxField] = $productAttribute;
                }
            }
            // merge with custom data fields mapping
            $dataFieldsMapping = array_merge($dataFieldsMapping, $this->getCustomDataFieldsMapping());
            $this->dataFieldsMapping = $dataFieldsMapping;
        }

        return $this->dataFieldsMapping;
    }

    /**
     * @param array $index
     * @param array $parentData
     * @param array $childIds
     * @param null $store
     * @return $this
     * @throws NoSuchEntityException
     */
    private function appendChildDataToParent(array &$index, array &$parentData, array $childIds, $store = null)
    {
        foreach ($childIds as $id) {
            if (!array_key_exists($id, $index)) {
                // child product doesn't exist in index
                continue;
            }
            if (!isset($index[$id][Config::getPreparedKey()]) && (!in_array($id, $this->relatedEntityPreparedDataList))) {
                $this->prepareFields($index[$id], $store);
            }
            $childData = $this->formatChildFields($index[$id], $id);
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
    private function formatChildFields(array $data, $childId)
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
                // collect child fields to use for add to schema fields

                $camelCaseKey = SimpleDataObjectConverter::snakeCaseToCamelCase($key);

                if ($camelCaseKey == $key && array_key_exists($key,$this->keyMap)){
                    //This could be a component product which is already formated with keys
                    $this->setChildrenSchemaFields($this->keyMap[$key]);
                }else{
                    $this->setChildrenSchemaFields($key);
                }

                $newKey = Config::CHILD_PRODUCT_FIELD_PREFIX.ucfirst($camelCaseKey);
                

                if (
                    in_array($key, [
                        Config::SPECIFIC_FIELD_KEY_UNIQUE_ID,
                        SimpleDataObjectConverter::snakeCaseToCamelCase(Config::SPECIFIC_FIELD_KEY_UNIQUE_ID)
                    ])
                ) {
                    $newKey = $variantIdKey;
                }

                $data[$newKey] = $value;
                if ($newKey != $key) {
                    unset($data[$key]);
                }
            }

            if (!isset($data[$variantIdKey])) {
                // omit children product if variant ID is not specified
                $this->childrenData[$childId] = [];
                return $this->childrenData[$childId];
            }
            $this->childrenData[$childId] = $data;
        }

        return $this->childrenData[$childId];
    }

    /**
     * Convert field values to needed format
     *
     * @param array $data
     * @return $this
     */
    private function prepareFieldValues(array &$data)
    {
        $optionTextPrefix = AttributeHelper::OPTION_TEXT_PREFIX.'_';
        foreach ($data as $key => $value) {
            $pureKey = str_replace($optionTextPrefix, '', $key);
            $optionTextKey = $optionTextPrefix.$pureKey;
            if (strpos($key, $optionTextPrefix) !== false) {
                // field with option labels
                if (!array_key_exists($pureKey, $data)) {
                    // don't proceed current data if option values doesn't exist
                    continue;
                }
                // result value as field with option labels
                $resultValue = $value;
            } else {
                // by default, result value as field without option labels
                $resultValue = $value;
                // try to find option labels
                if (array_key_exists($optionTextKey, $data)) {
                    // result value as field with option labels
                    $resultValue = $data[$optionTextKey];
                }
            }
            // these fields are already declared in schema as multivalued,
            // but make sure that they will be displayed correctly in the current data
            $specificMultiFields = [
                Config::SPECIFIC_FIELD_KEY_CATEGORY_PATH_ID,
                Config::CHILD_PRODUCTS_FIELD_KEY
            ];
            if (is_array($resultValue) && !in_array($key, $specificMultiFields)) {
                if (
                    isset($this->getIndexedFields()[$pureKey])
                    && empty($this->getIndexedFields()[$pureKey]['multiValued'])
                ) {
                    // not multivalued fields can't contain more than one value.
                    // for fields which should not have multiple values, multiply values occur for some types
                    // of products as a result of combining parent and child values (bundle, grouped).
                    $resultValue = (string) $resultValue[0];
                }
            }
            $data[$pureKey] = $resultValue;
        }
        // remove helper fields with option text prefix
        foreach ($data as $key => $value) {
            if (strpos($key, $optionTextPrefix) !== false) {
                unset($data[$key]);
            }
        }
        return $this;
    }

    /**
     * Build fields in feed data
     *
     * @param array $data
     * @return $this
     */
    private function buildFields(array &$data)
    {
        // clean additional helper fields
        foreach ($this->feedConfig->getAdditionalFields() as $field) {
            if (array_key_exists($field, $data)) {
                unset($data[$field]);
            }
        }
        // convert option values and labels only in labels
        $this->prepareFieldValues($data);

        return $this;
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
        $path = $urlKey.$this->getProductUrlSuffix($storeId);
        $url = $this->getStore($storeId)->getBaseUrl(UrlInterface::URL_TYPE_WEB,true) . $path;
        //$url = $this->getFrontendUrl($path);
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
    private function getFrontendUrl($routePath, $scope = '')
    {
        $this->frontendUrlBuilder->setScope($scope);

        return $this->frontendUrlBuilder->getUrl(
            $routePath,
            [
                '_current' => false,
                '_nosid' => true,
                '_query' => false
            ]
        );
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
                $this->keyMap[$newKey]=$key;
                unset($data[$key]);
            }
        }

        return $this;
    }

    /**
     * @param string $storeId
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    private function getStore($storeId = '')
    {
        return $this->storeManager->getStore($storeId);
    }

    /**
     * @param string $storeId
     * @return mixed
     * @throws NoSuchEntityException
     */
    private function getWebsite($storeId = '')
    {
        return $this->getStore($storeId)->getWebsite();
    }

    /**
     * Reset all cache handlers to initial state
     *
     * @return void
     */
    public function reset()
    {
        $this->indexedFields = [];
        $this->schema = [];
        $this->catalog = [];
        $this->fullFeed = [];
        $this->productUrlSuffix = [];
        $this->visibility = [];
        $this->processedImages = [];
        $this->childrenSchemaFields = [];
        $this->childrenData = [];
        $this->dataFieldsMapping = [];
        $this->relatedEntityPreparedDataList = [];
        $this->categoryDataHandler->reset();
        $this->imageDataHandler->reset();
    }
}
