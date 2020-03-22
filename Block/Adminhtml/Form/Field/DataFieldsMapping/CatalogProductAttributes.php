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
namespace Unbxd\ProductFeed\Block\Adminhtml\Form\Field\DataFieldsMapping;

use Magento\Framework\View\Element\Html\Select;
use Magento\Framework\View\Element\Context;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\EntityManager\MetadataPool;
use Unbxd\ProductFeed\Model\Feed\Config as FeedConfig;

/**
 * Class CatalogProductAttributes
 * @package Unbxd\ProductFeed\Block\Adminhtml\Form\Field\DataFieldsMapping
 */
class CatalogProductAttributes extends Select
{
    /**
     * @var ProductAttributeRepositoryInterface
     */
    protected $productAttributeRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var string|null
     */
    private $entityType = null;

    /**
     * @var null
     */
    private $catalogProductAttributesOptions = null;

    /**
     * CatalogProductAttributes constructor.
     * @param Context $context
     * @param ProductAttributeRepositoryInterface $productAttributeRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param MetadataPool $metadataPool
     * @param null $entityType
     * @param array $data
     */
    public function __construct(
        Context $context,
        ProductAttributeRepositoryInterface $productAttributeRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        MetadataPool $metadataPool,
        $entityType = null,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->productAttributeRepository = $productAttributeRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->metadataPool = $metadataPool;
        $this->entityType = $entityType;
    }

    /**
     * Sets name for input element
     *
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function getIdentifierField()
    {
        return $this->metadataPool->getMetadata($this->entityType)->getIdentifierField();
    }

    /**
     * @return array
     */
    private function getCustomAttributes()
    {
        $categoryKey = FeedConfig::FIELD_KEY_CATEGORY_DATA;
        $categoryLabel = sprintf('%s (%s)', ucfirst($categoryKey), $categoryKey);

        return [
            FeedConfig::FIELD_KEY_CATEGORY_DATA => $categoryLabel
        ];
    }

    /**
     * @return array|null
     * @throws \Exception
     */
    private function getCatalogProductAttributesOptions()
    {
        if (null === $this->catalogProductAttributesOptions) {
            $searchResult = $this->productAttributeRepository->getList($this->searchCriteriaBuilder->create());
            $attributes = [];

            /** @var \Magento\Eav\Model\Entity\Attribute\AbstractAttribute $productAttribute */
            foreach ($searchResult->getItems() as $productAttribute) {
                $attributeCode = $productAttribute->getAttributeCode();
                $frontendLabel = $productAttribute->getFrontendLabel()
                    ? $productAttribute->getFrontendLabel()
                    : ucwords(str_replace('_', ' ', $attributeCode));
                $label = sprintf('%s (%s)', $frontendLabel, $attributeCode);

                $attributes[$attributeCode] = __($label);
            }

            // added ID field
            $idField = $this->getIdentifierField();
            if (!array_key_exists($idField, $attributes)) {
                $attributes[$idField] = __(sprintf('ID (%s)', $idField));
            }

            // merge with custom attributes
            $attributes = array_merge($attributes, $this->getCustomAttributes());

            asort($attributes);
            array_unshift($attributes, __('-- Please Select --'));

            $this->catalogProductAttributesOptions = $attributes;
        }

        return $this->catalogProductAttributesOptions;
    }

    /**
     * Render block HTML
     *
     * @return string
     * @throws \Exception
     */
    public function _toHtml()
    {
        if (!$this->getOptions()) {
            foreach ($this->getCatalogProductAttributesOptions() as $value => $label) {
                $this->addOption($value, addslashes($label));
            }
        }

        return parent::_toHtml();
    }
}