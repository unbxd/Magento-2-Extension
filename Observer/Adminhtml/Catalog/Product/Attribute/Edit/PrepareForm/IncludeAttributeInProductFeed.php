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
namespace Unbxd\ProductFeed\Observer\Adminhtml\Catalog\Product\Attribute\Edit\PrepareForm;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Unbxd\ProductFeed\Helper\Module as ModuleHelper;
use Magento\Config\Model\Config\Source\Yesno;
use Unbxd\ProductFeed\Model\Config\Source\UnbxdFieldType;

/**
 * Class IncludeAttributeInProductFeed
 * @package Unbxd\ProductFeed\Observer\Adminhtml\Catalog\Product\Attribute\Edit\PrepareForm
 */
class IncludeAttributeInProductFeed implements ObserverInterface
{
    /**
     * @var ModuleHelper
     */
    protected $moduleHelper;

    /**
     * @var Yesno
     */
    protected $yesNo;


    /**
     * @var UnbxdFieldType
     */
    protected $unbxdFieldType;

    /**
     * IncludeAttributeInProductFeed constructor.
     * @param ModuleHelper $moduleHelper
     * @param Yesno $yesNo
     */
    public function __construct(
        ModuleHelper $moduleHelper,
        Yesno $yesNo,
        UnbxdFieldType $unbxdFieldType
    ) {
        $this->moduleHelper = $moduleHelper;
        $this->yesNo = $yesNo;
        $this->unbxdFieldType = $unbxdFieldType;
    }

    /**
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        if (!$this->moduleHelper->isModuleEnable()) {
            return $this;
        }

        /** @var \Magento\Framework\Data\Form $form */
        $form = $observer->getForm();
        $fieldset = $form->getElement('front_fieldset');
        if ($fieldset) {
            $fieldset->addField(
                'unbxd_multiselect_override',
                'select',
                [
                    'name'   => 'unbxd_multiselect_override',
                    'label'  => __('Unbxd MultiSelect override'),
                    'title'  => __('Unbxd MultiSelect override'),
                    'note' => __('Inverse the value of the computed multiselect value'),
                    'values' => $this->yesNo->toOptionArray(),
                ],
                '^'
            );

            $fieldset->addField(
                'unbxd_field_type',
                'select',
                [
                    'name'   => 'unbxd_field_type',
                    'label'  => __('Unbxd Attribute Data Type'),
                    'title'  => __('Unbxd Attribute Data Type'),
                    'note' => __('Used to indicate special attribute types'),
                    'values' => $this->unbxdFieldType->toOptionArray(),
                ],
                '^'
            );

            $fieldset->addField(
                'include_in_unbxd_product_feed',
                'select',
                [
                    'name'   => 'include_in_unbxd_product_feed',
                    'label'  => __('Include In Product Feed'),
                    'title'  => __('Include In Product Feed'),
                    'note' => __('Specify whether or not the attribute will be included in the product feed (added by Unbxd)'),
                    'values' => $this->yesNo->toOptionArray(),
                ],
                '^'
            );

        }

        return $this;
    }
}