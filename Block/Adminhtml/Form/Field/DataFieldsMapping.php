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
namespace Unbxd\ProductFeed\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\Factory as ElementFactory;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Unbxd\ProductFeed\Block\Adminhtml\Form\Field\DataFieldsMapping\UnbxdFields;
use Unbxd\ProductFeed\Block\Adminhtml\Form\Field\DataFieldsMapping\CatalogProductAttributes;
use Unbxd\ProductFeed\Block\Adminhtml\Form\Field\DataFieldsMapping\Status;
use Unbxd\ProductFeed\Model\Feed\Config as FeedConfig;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class DataFieldsMapping
 * @package Unbxd\ProductFeed\Block\Adminhtml\Form\Field
 */
class DataFieldsMapping extends AbstractFieldArray
{
    /**
     * @var ElementFactory
     */
    protected $elementFactory;

    /**
     * @var UnbxdFields
     */
    protected $unbxdFieldsRenderer;

    /**
     * @var CatalogProductAttributes
     */
    protected $catalogProductAttributesRenderer;

    /**
     * @var Status
     */
    protected $statusRenderer;

    /**
     * @var FeedConfig
     */
    private $feedConfig;

    /**
     * @var ProductAttributeRepositoryInterface
     */
    private $productAttributeRepository;

    /**
     * @var string
     */
    private $defaultDataFieldsMappingTemplate;

    /**
     * @var array|null
     */
    private $defaultDataFieldsMapping = null;

    /**
     * DataFieldsMapping constructor.
     * @param Context $context
     * @param ElementFactory $elementFactory
     * @param FeedConfig $feedConfig
     * @param ProductAttributeRepositoryInterface $productAttributeRepository
     * @param null $defaultDataFieldsMappingTemplate
     * @param array $data
     */
    public function __construct(
        Context $context,
        ElementFactory $elementFactory,
        FeedConfig $feedConfig,
        ProductAttributeRepositoryInterface $productAttributeRepository,
        $defaultDataFieldsMappingTemplate = null,
        array $data = []
    ) {
        $this->elementFactory = $elementFactory;
        $this->feedConfig = $feedConfig;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->defaultDataFieldsMappingTemplate = isset($data['defaultDataFieldsMappingTemplate'])
            ? $data['defaultDataFieldsMappingTemplate']
            : $defaultDataFieldsMappingTemplate;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve Unbxd fields column renderer
     *
     * @return \Magento\Framework\View\Element\BlockInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getUnbxdFieldsRenderer()
    {
        if (!$this->unbxdFieldsRenderer) {
            $this->unbxdFieldsRenderer = $this->getLayout()->createBlock(
                'Unbxd\ProductFeed\Block\Adminhtml\Form\Field\DataFieldsMapping\UnbxdFields',
                'product.feed.data.mapping.unbxd.fields',
                ['data' => ['is_render_to_js_template' => true]]
            );
            $this->unbxdFieldsRenderer->setClass(
                'select admin__control-select data-mapping-unbxd-fields-select'
            );
        }
        return $this->unbxdFieldsRenderer;
    }

    /**
     * Retrieve catalog product attributes column renderer
     *
     * @return \Magento\Framework\View\Element\BlockInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getCatalogProductAttributesRenderer()
    {
        if (!$this->catalogProductAttributesRenderer) {
            $this->catalogProductAttributesRenderer = $this->getLayout()->createBlock(
                'Unbxd\ProductFeed\Block\Adminhtml\Form\Field\DataFieldsMapping\CatalogProductAttributes',
                'product.feed.data.mapping.catalog.product.attributes',
                ['data' => ['is_render_to_js_template' => true]]
            );
            $this->catalogProductAttributesRenderer->setClass(
                'select admin__control-select data-mapping-catalog-product-attributes-select'
            );
        }
        return $this->catalogProductAttributesRenderer;
    }

    /**
     * Retrieve status (enable/disabled) column renderer
     *
     * @return \Magento\Framework\View\Element\BlockInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getStatusRenderer()
    {
        if (!$this->statusRenderer) {
            $this->statusRenderer = $this->getLayout()->createBlock(
                'Unbxd\ProductFeed\Block\Adminhtml\Form\Field\DataFieldsMapping\Status',
                'product.feed.data.mapping.status',
                ['data' => ['is_render_to_js_template' => true]]
            );
            $this->statusRenderer->setClass(
                'select admin__control-select data-mapping-status-select'
            );
        }
        return $this->statusRenderer;
    }

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->addColumn(
            'unbxd_field',
            [
                'label' => __('Unbxd Field'),
                'renderer' => $this->getUnbxdFieldsRenderer()
            ]
        );
        $this->addColumn(
            'product_attribute',
            [
                'label' => __('Product Attribute'),
                'renderer' => $this->getCatalogProductAttributesRenderer()
            ]
        );
        $this->addColumn(
            'is_enabled',
            [
                'label' => __('Enabled'),
                'renderer'  => $this->getStatusRenderer(),
            ]
        );

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Mapping');
        parent::_construct();
    }

    /**
     * Build default data fields mapping. Init if needed
     *
     * @return array|null
     */
    public function getDefaultDataFieldsMapping()
    {
        if (null === $this->defaultDataFieldsMapping) {
            $fields = array_flip($this->feedConfig->getDefaultDataFieldsMappingStorage());
            ksort($fields);

            $resultFields = [];
            foreach ($fields as $unbxdFieldCode => $productAttributeCode) {
                $frontendUnbxdFieldLabel = sprintf(
                    '%s (%s)',
                    $this->convertToLabel($unbxdFieldCode),
                    $unbxdFieldCode
                );

                $productAttributeFrontendLabel = $this->getProductAttributeFrontendLabel($productAttributeCode);
                $frontendProductAttributeLabel = sprintf(
                    '%s (%s)',
                    $productAttributeFrontendLabel
                            ? $productAttributeFrontendLabel
                            : $this->convertToLabel($productAttributeCode),
                    $productAttributeCode
                );

                $resultFields[$frontendUnbxdFieldLabel] = $frontendProductAttributeLabel;
            }

            $this->defaultDataFieldsMapping = $resultFields;
        }

        return $this->defaultDataFieldsMapping;
    }

    /**
     * @param $attributeCode
     * @return string|null
     */
    private function getProductAttributeFrontendLabel($attributeCode)
    {
        try {
            /** @var \Magento\Catalog\Api\Data\ProductAttributeInterface $productAttribute */
            $productAttribute = $this->productAttributeRepository->get($attributeCode);
            $frontendLabel = $productAttribute->getFrontendLabel();
        } catch (NoSuchEntityException $e) {
            $frontendLabel = null;
        }

        return $frontendLabel;
    }

    /**
     * @param $value
     * @return \Magento\Framework\Phrase
     */
    private function convertToLabel($value)
    {
        return __(ucwords(str_replace('_', ' ', $value)));
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\ValidatorException
     */
    private function renderDefaultDataFieldsMappingHtml()
    {
        return $this->fetchView($this->getTemplateFile($this->defaultDataFieldsMappingTemplate));
    }

    /**
     * Get the grid and scripts contents with custom template
     *
     * @param AbstractElement $element
     * @return string
     * @throws \Magento\Framework\Exception\ValidatorException
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $html = parent::_getElementHtml($element);
        $html .= $this->renderDefaultDataFieldsMappingHtml();

        return $html;
    }

    /**
     * Prepare existing row data object
     *
     * @param \Magento\Framework\DataObject $row
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareArrayRow(\Magento\Framework\DataObject $row)
    {
        $optionExtraAttr = [];
        $optionExtraAttr['option_' . $this->getUnbxdFieldsRenderer()->calcOptionHash(
            $row->getData('unbxd_field')
        )] = 'selected="selected"';
        $optionExtraAttr['option_' . $this->getCatalogProductAttributesRenderer()->calcOptionHash(
            $row->getData('product_attribute')
        )] = 'selected="selected"';
        $optionExtraAttr['option_' . $this->getStatusRenderer()->calcOptionHash(
            $row->getData('is_enabled')
        )] = 'selected="selected"';

        $row->setData(
            'option_extra_attrs',
            $optionExtraAttr
        );

        return;
    }
}