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
namespace Unbxd\ProductFeed\Model\Eav\Indexer\Full\DataSourceProvider;

use Magento\Eav\Model\Entity\Attribute\AttributeInterface;
use Unbxd\ProductFeed\Model\ResourceModel\Eav\Indexer\Full\DataSourceProvider\AbstractAttribute as ResourceModel;
use Unbxd\ProductFeed\Helper\AttributeHelper as AttributeHelper;
use Unbxd\ProductFeed\Logger\LoggerInterface;

/**
 * Class AbstractAttribute
 * @package Unbxd\ProductFeed\Model\Eav\Indexer\Full\DataSourceProvider
 */
abstract class AbstractAttribute
{
    /**
     * Local cache for attributes by code
     *
     * @var array
     */
    protected $attributesByCode = [];

    /**
     * Local cache for attributes by ID
     *
     * @var array
     */
    protected $attributesById = [];

    /**
     * Local cache for attribute ids by table
     *
     * @var array
     */
    protected $attributeIdsByTable = [];

    /**
     * @var AttributeHelper
     */
    protected $attributeHelper;

    /**
     * @var ResourceModel
     */
    protected $resourceModel;

    /**
     * @var array
     */
    protected $fields = [];

    /**
     * @var array
     */

    protected $skipChildProductValuesForAttribute = [];

    /**
     * @var array
     */
    protected $indexedFields = [];

    protected $logger;

    /**
     * @var array
     */
    protected $indexedBackendModels = [
        \Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend::class,
        \Magento\Eav\Model\Entity\Attribute\Backend\Datetime::class,
        \Magento\Catalog\Model\Attribute\Backend\Startdate::class,
        \Magento\Catalog\Model\Product\Attribute\Backend\Boolean::class,
        \Magento\Eav\Model\Entity\Attribute\Backend\DefaultBackend::class,
        \Magento\Catalog\Model\Product\Attribute\Backend\Weight::class,
        \Magento\Catalog\Model\Product\Attribute\Backend\Price::class,
    ];

    /**
     * AbstractAttribute constructor.
     * @param ResourceModel $resourceModel
     * @param AttributeHelper $attributeHelper
     * @param array $indexedBackendModels
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        ResourceModel $resourceModel,
        AttributeHelper $attributeHelper,
        LoggerInterface $logger,
        array $indexedBackendModels = []
    ) {
        $this->resourceModel = $resourceModel;
        $this->attributeHelper = $attributeHelper;
        $this->logger=$logger;

        if (is_array($indexedBackendModels) && !empty($indexedBackendModels)) {
            $indexedBackendModels = array_values($indexedBackendModels);
            $this->indexedBackendModels = array_merge($indexedBackendModels, $this->indexedBackendModels);
        }

        $this->initDefaultAttributes();
        $this->initAttributes();
    }

    /**
     * List of fields generated from the attributes list.
     *
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Set field options generated from the attributes list.
     *
     * @param $field
     * @param array $options
     */
    public function setField($field, $options = [])
    {
        if (!array_key_exists($field, $this->getFields())) {
            $this->fields[$field] = $options;
        }
    }

    /**
     * List of indexed fields generated from the attributes list.
     *
     * @return array
     */
    public function getIndexedFields()
    {
        return $this->indexedFields;
    }

    /**
     * Set indexed field generated from the attributes list.
     *
     * @param $field
     */
    public function setIndexedField($field)
    {
        if (!array_key_exists($field, $this->getIndexedFields())) {
            $this->indexedFields[$field] = null;
        }
    }

    /**
     * Load default attribute codes from the database.
     *
     * @return array
     */
    protected function loadDefaultAttributeFields()
    {
        return $this->resourceModel->getDefaultAttributeFields();
    }

    /**
     * Load attribute data from the database.
     *
     * @param $storeId
     * @param array $entityIds
     * @param $tableName
     * @param array $attributeIds
     * @return array
     * @throws \Exception
     */
    protected function loadAttributesRawData($storeId, array $entityIds, $tableName, array $attributeIds)
    {
        return $this->resourceModel->getAttributesRawData($storeId, $entityIds, $tableName, $attributeIds);
    }

    /**
     * Init default attributes.
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function initDefaultAttributes()
    {
        $attributesCodes = $this->loadDefaultAttributeFields();
        if (!empty($attributesCodes)) {
            // merge with store/website related fields
            $attributesCodes = array_merge_recursive(array_flip($attributesCodes), ['store_id', 'website_id']);
            foreach ($attributesCodes as $attributeCode) {
                $attribute = $this->attributeHelper->initAttributeByCode($attributeCode);
                if ($attributeId = $attribute->getId()) {
                    $this->attributesByCode[$attributeCode] = $attribute;
                    // collect attributes fields (use in feed operation)
                    $this->initFields($attribute);
                    // add default attributes to indexed fields (use in feed operation)
                    $this->setIndexedField($attributeCode);
                }
            }
            // try detect fields which are not like attribute and collect theirs options
            $diffAttributes = array_diff_key(array_flip($attributesCodes), $this->attributesByCode);
            if (!empty($diffAttributes)) {
                foreach ($diffAttributes as $fieldName => $value) {
                    // collect attributes fields (use in feed operation)
                    $this->setField($fieldName, $this->attributeHelper->getSpecificFieldOptions($fieldName));
                }
            }
        }

        return $this;
    }

    /**
     * Init attributes.
     *
     * @return $this
     */
    private function initAttributes()
    {
        $attributeCollection = $this->attributeHelper->getAttributeCollection();
        foreach ($attributeCollection as $attribute) {
            if ($this->canIndexAttribute($attribute)) {
                $attributeId = (int) $attribute->getId();
                $this->attributesById[$attributeId] = $attribute;
                if ($attribute->getData('consider_attribute_onlyat_parent',false)){
                    $this->skipChildProductValuesForAttribute[] = $attribute->getAttributeCode();
                }
                $this->attributeIdsByTable[$attribute->getBackendTable()][] = $attributeId;
                // collect attributes fields (use in feed operation)
                $this->initFields($attribute);
            }
        }

        return $this;
    }

    /**
     * Check if an attribute can be indexed.
     *
     * @param AttributeInterface $attribute
     * @return bool
     */
    private function canIndexAttribute(AttributeInterface $attribute)
    {
        $canIndex = ($attribute->getBackendType() != 'static')
            && ($attribute->getAttributeCode() !== 'price') && (bool) $attribute->getIncludeInUnbxdProductFeed();
        if ($canIndex && $attribute->getBackendModel()) {
            foreach ($this->indexedBackendModels as $indexedBackendModel) {
                $canIndex = is_a($attribute->getBackendModel(), $indexedBackendModel, true);
                if ($canIndex) {
                    return $canIndex;
                }
            }
        }

        return $canIndex;
    }

    /**
     * Create a mapping field from an attribute.
     *
     * @param AttributeInterface $attribute
     * @return $this
     */
    private function initFields(AttributeInterface $attribute)
    {
        $fieldName = $attribute->getAttributeCode();
        $this->setField($fieldName, $this->attributeHelper->getFieldOptions($attribute));

        return $this;
    }
}
