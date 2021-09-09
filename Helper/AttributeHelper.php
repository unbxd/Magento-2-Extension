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

use Magento\Catalog\Api\Data\EavAttributeInterface;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory as EavAttributeFactory;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Eav\Model\Entity\Attribute\AttributeInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Unbxd\ProductFeed\Helper\Data as HelperData;
use Unbxd\ProductFeed\Model\Feed\Config as FeedConfig;

/**
 * Product feed attributes helper.
 *
 * Class AttributeHelper
 * @package Unbxd\ProductFeed\Helper
 */
class AttributeHelper extends AbstractHelper
{
    /**
     * @var string
     */
    const OPTION_TEXT_PREFIX = 'option_text';

    /**
     * @var EavAttributeFactory
     */
    private $attributeFactory;

    /**
     * @var AttributeCollectionFactory
     */
    private $attributeCollectionFactory;

    /**
     * @var HelperData
     */
    private $helperData;

    /**
     * @var array
     */
    private $storeAttributes = [];

    /**
     * @var array
     */
    private $attributeOptionTextCache = [];

    /**
     * @var array
     */
    private $attributeMappers = [];

    /**
     * AttributeHelper constructor.
     * @param Context $context
     * @param EavAttributeFactory $attributeFactory
     * @param AttributeCollectionFactory $collectionFactory
     * @param Data $helperData
     */
    public function __construct(
        Context $context,
        EavAttributeFactory $attributeFactory,
        AttributeCollectionFactory $collectionFactory,
        HelperData $helperData
    ) {
        parent::__construct($context);
        $this->attributeFactory = $attributeFactory;
        $this->attributeCollectionFactory = $collectionFactory;
        $this->helperData = $helperData;
    }

    /**
     * Retrieve a new product attribute collection.
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    public function getAttributeCollection()
    {
        return $this->attributeCollectionFactory->create();
    }

    /**
     * Retrieve a new product attribute instance by code.
     *
     * @param $attributeCode
     * @return \Magento\Catalog\Model\ResourceModel\Eav\Attribute
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function initAttributeByCode($attributeCode)
    {
        $attribute = $this->attributeFactory->create();
        $attribute->loadByCode(\Magento\Catalog\Model\Product::ENTITY, $attributeCode);
        return $attribute;
    }

    /**
     * Parse attribute to get additional field options
     *
     * @param AttributeInterface $attribute Product attribute.
     * @return array
     */
    public function getAdditionalFieldOptions(AttributeInterface $attribute)
    {
        $options = [
            'is_searchable' => $attribute->getIsSearchable(),
            'is_filterable' => $attribute->getIsFilterable() || $attribute->getIsFilterableInSearch(),
            'search_weight' => $attribute->getSearchWeight(),
            'is_used_for_sort_by' => $attribute->getUsedForSortBy(),
        ];

        if ($attribute->getIsUsedInSpellcheck()) {
            $options['is_used_in_spellcheck'] = true;
        }

        if ($attribute->getIsDisplayedInAutocomplete()) {
            $options['is_filterable'] = true;
        }

        return $options;
    }

    /**
     * Get mapping field type for an attribute.
     *
     * @param AttributeInterface $attribute Product attribute.
     * @return string
     */
    public function getFieldType(AttributeInterface $attribute)
    {
        if ($type = $attribute->getData("unbxd_field_type")){
            return $type;
        }
        $type = FeedConfig::FIELD_TYPE_TEXT;
        if ($this->isFieldBool($attribute)) {
            $type = FeedConfig::FIELD_TYPE_BOOL;
        } else if ($this->isFieldLongText($attribute)) {
            $type = FeedConfig::FIELD_TYPE_LONGTEXT;
        } else if ($this->isFieldDecimal($attribute)) {
            $type = FeedConfig::FIELD_TYPE_DECIMAL;
        } else if ($this->isFieldDatetime($attribute)) {
            $type = FeedConfig::FIELD_TYPE_DATE;
        }

        return $type;
    }

    /**
     * Check if field is bool type.
     *
     * @param AttributeInterface $attribute
     * @return bool
     */
    public function isFieldBool(AttributeInterface $attribute)
    {
        $specificStaticBoolAttributes = ['has_options', 'required_options'];
        return (bool) ($attribute->getSourceModel() == \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class)
        || in_array($attribute->getAttributeCode(), $specificStaticBoolAttributes);
    }

    /**
     * Check if field is number type.
     *
     * @param AttributeInterface $attribute Product attribute.
     * @return bool
     */
    public function isFieldNumber(AttributeInterface $attribute)
    {
        return (bool) (($attribute->getBackendType() == 'int') || ($attribute->getBackendType() == 'varchar'))
            && (($attribute->getFrontendInput() == 'select') || ($attribute->getFrontendInput() == 'multiselect'));
    }

    /**
     * Check if field is long text type.
     *
     * @param AttributeInterface $attribute
     * @return bool
     */
    public function isFieldLongText(AttributeInterface $attribute)
    {
        return (bool) ($attribute->getBackendType() == 'varchar') && ($attribute->getFrontendInput() == 'textarea');
    }

    /**
     * Check if field is decimal type.
     *
     * @param AttributeInterface $attribute
     * @return bool
     */
    public function isFieldDecimal(AttributeInterface $attribute)
    {
        return (bool) ($attribute->getBackendType() == 'decimal')
            || ($attribute->getFrontendClass() == 'validate-digits')
            || ($attribute->getFrontendClass() == 'validate-number');
    }

    /**
     * Check if field is datetime type.
     *
     * @param AttributeInterface $attribute
     * @return bool
     */
    public function isFieldDatetime(AttributeInterface $attribute)
    {
        return (bool) ($attribute->getBackendType() == 'datetime')
            || (($attribute->getBackendType() == 'static') && ($attribute->getFrontendInput() == 'date'));
    }

    /**
     * Check if field is text type.
     *
     * @param AttributeInterface $attribute
     * @return bool
     */
    public function isFieldText(AttributeInterface $attribute)
    {
        return (bool) ($attribute->getBackendType() == 'varchar') && ($attribute->getFrontendInput() == 'text')
            || (($attribute->getBackendType() == 'int') && ($attribute->getFrontendInput() == 'multiselect'));
    }

    /**
     * Check if field is multivalued.
     *
     * @param AttributeInterface $attribute Product attribute.
     * @return bool
     */
    public function isFieldMultivalued(AttributeInterface $attribute)
    {
        $value =  (bool) (in_array($attribute->getBackendType(), ['varchar', 'text'])
            && ($attribute->getFrontendInput() == 'multiselect'));
        if ($attribute->getData('unbxd_multiselect_override',false)){
            return !$value;
        }else{
            return $value;
        }
    }

    /**
     * Parse attribute raw value (as saved in the database) to prepare the indexed value.
     * For attribute using options the option value is also added to the result which contains two keys :
     *   - one is "attribute_code" and contained the option id(s)
     *   - the other one is "option_text_attribute_code" and contained option value(s)
     * All value are transformed into arrays to have a more simple management of
     * multivalued attributes merging on composite products).
     *
     * @param AttributeInterface $attribute
     * @param $storeId
     * @param $value
     * @return array
     */
    public function prepareIndexValue(AttributeInterface $attribute, $storeId, $value)
    {
        $attributeCode = $attribute->getAttributeCode();
        $values = [];

        $mapperKey = 'simple_' . $attribute->getId();

        if ($this->isFieldDatetime($attribute)) {
            $value = $this->helperData->formatDateTime($value);
        }

        if (!isset($this->attributeMappers[$mapperKey])) {
            $this->attributeMappers[$mapperKey] = function ($value) use ($attribute) {
                return $this->prepareSimpleIndexAttributeValue($attribute, $value);
            };
        }

        if ($attribute->usesSource() && !is_array($value)) {
            $value = explode(',', $value);
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        $value = array_map($this->attributeMappers[$mapperKey], $value);
        if ($attribute->getData("unbxd_field_type") && $attribute->getData("unbxd_field_type") == "bool"){
            if (($value && !is_array($value)) || (is_array($value) && $value[0])){ 
                $values[$attributeCode] = "True";
            }else{
                $values[$attributeCode]="False";
            }
             return $values;
        }
        $value = array_filter($value);
        $value = array_values($value);
        $values[$attributeCode] = $value;

        if ($attribute->usesSource()) {
            $optionTextFieldName = $this->getOptionTextFieldName($attributeCode);
            $optionTextValues = $this->getIndexOptionsText($attribute, $storeId, $value);
            $this->normalizeOptionTextValues($optionTextValues);
            // filter empty values, not using array_filter here because it could remove "0" string from values.
            $optionTextValues = array_diff(array_map('trim', $optionTextValues), ['', null, false]);
            $optionTextValues = array_values($optionTextValues);
            $values[$optionTextFieldName] = $optionTextValues;
        }

        return array_filter($values);
    }

    /**
     * Transform an array of options text values into an simple array
     *
     * @param array $optionTextValues
     * @return $this
     */
    private function normalizeOptionTextValues(array &$optionTextValues)
    {
        foreach ($optionTextValues as $key => $optionTextValue) {
            if (!is_array($optionTextValue)) {
                continue;
            }
            $values = array_values($optionTextValue);
            // get first valid value
            $optionTextValues[$key] = (string) array_shift($values);
        }
        return $this;
    }

    /**
     * Transform an array of options ids into an arrays of option values for attribute that uses a source.
     * Values are localized for a store id.
     *
     * @param AttributeInterface $attribute
     * @param $storeId
     * @param array $optionIds
     * @return array
     */
    public function getIndexOptionsText(AttributeInterface $attribute, $storeId, array $optionIds)
    {
        $mapperKey = "options_". $attribute->getId()."_". $storeId;

        if (!isset($this->attributeMappers[$mapperKey])) {
            $this->attributeMappers[$mapperKey] = function ($optionId) use ($attribute, $storeId) {
                return $this->getIndexOptionText($attribute, $storeId, $optionId);
            };
        }

        $optionValues = array_map($this->attributeMappers[$mapperKey], $optionIds);

        return $optionValues;
    }

    /**
     * Transform a field name into it's option value field form.
     *
     * @param $fieldName
     * @return string
     */
    public function getOptionTextFieldName($fieldName)
    {
        return self::OPTION_TEXT_PREFIX."_".$fieldName;
    }

    /**
     * Transform an options id into an array of option value for attribute that uses a source.
     * Value is localized for a store id.
     *
     * @param AttributeInterface $attribute
     * @param $storeId
     * @param $optionId
     * @return mixed
     */
    public function getIndexOptionText(AttributeInterface $attribute, $storeId, $optionId)
    {
        $attribute = $this->getAttributeByStore($attribute, $storeId);
        $attributeId = $attribute->getAttributeId();

        if (
            !isset($this->attributeOptionTextCache[$storeId])
            || !isset($this->attributeOptionTextCache[$storeId][$attributeId])
        ) {
            $this->attributeOptionTextCache[$storeId][$attributeId] = [];
        }

        if (!isset($this->attributeOptionTextCache[$storeId][$attributeId][$optionId])) {
            $optionValue = $attribute->getSource()->getIndexOptionText($optionId);
            if ($this->getFieldType($attribute) == FeedConfig::FIELD_TYPE_BOOL) {
                $optionValue = $attribute->getStoreLabel($storeId);
            }
            $this->attributeOptionTextCache[$storeId][$attributeId][$optionId] = $optionValue;
        }

        return $this->attributeOptionTextCache[$storeId][$attributeId][$optionId];
    }

    /**
     * Returns field use for filtering for an attribute.
     *
     * @param AttributeInterface $attribute.
     * @return string
     */
    public function getFilterField(AttributeInterface $attribute)
    {
        $field = $attribute->getAttributeCode();

        if ($attribute->usesSource()) {
            $field = $this->getOptionTextFieldName($field);
        }

        return $field;
    }

    /**
     * Ensure types of numerical values is correct before indexing.
     *
     * @param AttributeInterface $attribute
     * @param $value
     * @return bool|float|int
     */
    private function prepareSimpleIndexAttributeValue(AttributeInterface $attribute, $value)
    {
        if ($this->getFieldType($attribute) == FeedConfig::FIELD_TYPE_BOOL) {
            $value = boolval($value);
        } elseif ($attribute->getBackendType() == 'decimal') {
            $value = floatval($value);
        } elseif ($attribute->getBackendType() == 'int') {
            $value = intval($value);
        } elseif (($attribute->getFrontendClass() == 'validate-digits')
            || ($attribute->getFrontendClass() == 'validate-number')) {
            $value = floatval(filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION));
        }

        return $value;
    }

    /**
     * Load the localized version of an attribute.
     * This code uses a local cache to ensure correct performance during indexing.
     *
     * @param $attribute
     * @param $storeId
     * @return mixed
     */
    private function getAttributeByStore($attribute, $storeId)
    {
        $attributeId = $this->getAttributeId($attribute);

        if (!isset($this->storeAttributes[$storeId]) || !isset($this->storeAttributes[$storeId][$attributeId])) {
            /**
             * @var EavAttributeInterface
             */
            $storeAttribute = $this->attributeFactory->create();
            $storeAttribute->load($attributeId)->setStoreId($storeId);
            $this->storeAttributes[$storeId][$attributeId] = $storeAttribute;
        }

        return $this->storeAttributes[$storeId][$attributeId];
    }

    /**
     * This util method is used to ensure the attribute is an integer and uses it's id if it is an object.
     *
     * @param $attribute
     * @return mixed
     */
    private function getAttributeId($attribute)
    {
        $attributeId = $attribute;

        if (is_object($attribute)) {
            $attributeId = $attribute->getAttributeId();
        }

        return $attributeId;
    }

    /**
     * Prepare field options for feed schema data
     *
     * @param AttributeInterface $attribute
     * @param bool $includeAdditionalMapOptions
     * @return array
     */
    public function getFieldOptions(AttributeInterface $attribute, $includeAdditionalMapOptions = false)
    {
        $fieldName = $attribute->getAttributeCode();
        $fieldType = $this->getFieldType($attribute);
        $isFieldMultivalued = $this->isFieldMultivalued($attribute);

        $fieldOptions = [
            'fieldName' => (string) $fieldName,
            'dataType' => (string) $fieldType,
            'multiValued' => (boolean) $isFieldMultivalued,
            'autoSuggest' => FeedConfig::DEFAULT_SCHEMA_AUTO_SUGGEST_FIELD_VALUE,
        ];

        if ($includeAdditionalMapOptions) {
            $additionalOptions = $this->getAdditionalFieldOptions($attribute);
            $fieldOptions['additionalOptions'] = $additionalOptions;
        }

        return $fieldOptions;
    }

    /**
     * Prepare specific field options for feed schema data
     *
     * @param $fieldName
     * @return array
     */
    public function getSpecificFieldOptions($fieldName)
    {
        $fieldType = FeedConfig::FIELD_TYPE_TEXT;
        if (($fieldName == 'price') || ($fieldName == 'original_price')) {
            $fieldType = FeedConfig::FIELD_TYPE_DECIMAL;
        } else if ($fieldName == 'quantity_and_stock_status') {
            $fieldType = FeedConfig::FIELD_TYPE_BOOL;
        }

        $multiValued = ($fieldName != 'category') ? false : true;
        $fieldOptions = [
            'fieldName' => (string) $fieldName,
            'dataType' => (string) $fieldType,
            'multiValued' => $multiValued,
            'autoSuggest' => FeedConfig::DEFAULT_SCHEMA_AUTO_SUGGEST_FIELD_VALUE,
        ];

        return $fieldOptions;
    }

    /**
     * Append indexed fields to index data (use for build feed schema)
     *
     * @param array $indexData
     * @param array $fields
     * @return bool
     */
    public function appendSpecificIndexedFields(array &$indexData, array $fields)
    {
        if (empty($fields)) {
            return false;
        }

        $indexedFields = array_key_exists('fields', $indexData) ? $indexData['fields'] : [];
        foreach ($fields as $field) {
            $fieldOptions = $this->getSpecificFieldOptions($field);
            $indexData['fields'][$field] = $fieldOptions;
        }

        return true;
    }
}
