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
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function initFeed(array $index)
    {
        $this->prepareData($index);
        $this->buildFeed();

        return $this->getFullFeed();
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
    private function buildSchemaFields(array $fields)
    {
        if (empty($fields)) {
            $this->logger->info('Can\'t prepare schema fields. Index data is empty.');
            return $this;
        }

        // add main product fields to schema
        $excludedFields = $this->feedConfig->getExcludedFields();
        $dataFieldsMapping = $this->buildDataFieldsMapping();
        foreach ($fields as $fieldCode => &$fieldData) {
            if (in_array($fieldCode, $excludedFields)) {
                unset($fields[$fieldCode]);
            }
            if (array_key_exists($fieldCode, $dataFieldsMapping)) {
                $fieldKey = $dataFieldsMapping[$fieldCode];
                $fieldData['fieldName'] = $fieldKey;
                $fields[$fieldKey] = $fieldData;
                // unset mapped field only in case if it is not the same as the original
                if ($fieldCode != $fieldKey) {
                    unset($fields[$fieldCode]);
                }
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
                // apply data fields mapping
                $this->applyWebsiteStoreFields($data)
                    ->applyDataFieldsMapping($data);

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
    private function buildFeed()
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

            $this->setFullFeed($this->fullFeed);
        }

        return $this;
    }

    /**
     * Check and add 'website_id' and 'store_id' fields to formed feed
     * (in case if they missing in some reason)
     *
     * @param array $data
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function applyWebsiteStoreFields(array &$data)
    {
        $storeId = $this->getStore()->getId();
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
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function applyDataFieldsMapping(array &$data)
    {
        $dataFieldsMapping = $this->buildDataFieldsMapping();
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
                    $imageUrl = $this->imageDataHandler->getImageUrl($value);
                    if ($imageUrl) {
                        $data[$unbxdField] = $imageUrl;
                        unset($data[$productAttribute]);
                    }
                    break;
                case Config::FIELD_KEY_CATEGORY_DATA:
                    $categoryData = $this->categoryDataHandler->buildCategoryList($data[Config::FIELD_KEY_CATEGORY_DATA]);
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
                    if ($productAttribute != ProductInterface::SKU) {
                        unset($data[$productAttribute]);
                    }
                    break;
            }
        }

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
                // in case if child data not prepared yet
                $this->applyWebsiteStoreFields($index[$id])
                    ->applyDataFieldsMapping($index[$id])
                    ->filterFields($index[$id]);
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

            $variantIdKey = SimpleDataObjectConverter::snakeCaseToCamelCase(
                Config::CHILD_PRODUCT_FIELD_VARIANT_ID
            );
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
                // omit children product if variant ID is not specified
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
     * Retrieve product frontend url
     *
     * @param $urlKey
     * @param $storeId
     * @return mixed
     */
    private function buildProductUrl($urlKey, $storeId)
    {
        $path = sprintf('%s%s', $urlKey, $this->getProductUrlSuffix($storeId));
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
    private function getFrontendUrl($routePath, $scope = '')
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
     * Reset all cache handlers to initial state
     *
     * @return void
     */
    public function reset()
    {
        $this->schema = [];
        $this->catalog = [];
        $this->fullFeed = [];
        $this->productUrlSuffix = [];
        $this->visibility = [];
        $this->childrenSchemaFields = [];
        $this->childrenData = [];
        $this->dataFieldsMapping = [];
    }
}