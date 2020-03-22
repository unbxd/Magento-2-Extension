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
use Unbxd\ProductFeed\Model\OptionSource\DataFieldsMapping\UnbxdFields as OptionSource;

/**
 * Class UnbxdFields
 * @package Unbxd\ProductFeed\Block\Adminhtml\Form\Field\DataFieldsMapping
 */
class UnbxdFields extends Select
{
    /**
     * @var OptionSource
     */
    private $optionSource;

    /**
     * UnbxdFields constructor.
     * @param Context $context
     * @param OptionSource $optionSource
     * @param array $data
     */
    public function __construct(
        Context $context,
        OptionSource $optionSource,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->optionSource = $optionSource;
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
     * @return array
     */
    private function getUnbxdFieldsOptions()
    {
        $fields = $this->optionSource->toOptionArray();

        asort($fields);
        array_unshift($fields, __('-- Please Select --'));

        return $fields;
    }

    /**
     * Render block HTML
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function _toHtml()
    {
        if (!$this->getOptions()) {
            foreach ($this->getUnbxdFieldsOptions() as $key => $label) {
                $this->addOption($key, addslashes($label));
            }
        }

        return parent::_toHtml();
    }
}