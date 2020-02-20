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
use Magento\Config\Model\Config\Source\Yesno;

/**
 * Class Status
 * @package Unbxd\ProductFeed\Block\Adminhtml\Form\Field\DataFieldsMapping
 */
class Status extends Select
{
    /**
     * @var Yesno
     */
    protected $yesNo;

    /**
     * ParameterState constructor.
     * @param Yesno $yesNo
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        Yesno $yesNo,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->yesNo = $yesNo;
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
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml()
    {
        if (!$this->getOptions()) {
            $options = $this->yesNo->toOptionArray();
            foreach ($options as $option) {
                if (isset($option['value']) && isset($option['label'])) {
                    $this->addOption($option['value'], $option['label']);
                }
            }
        }

        return parent::_toHtml();
    }
}