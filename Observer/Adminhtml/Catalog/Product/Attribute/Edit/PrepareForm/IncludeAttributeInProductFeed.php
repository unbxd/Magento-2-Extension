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
     * IncludeAttributeInProductFeed constructor.
     * @param ModuleHelper $moduleHelper
     * @param Yesno $yesNo
     */
    public function __construct(
        ModuleHelper $moduleHelper,
        Yesno $yesNo
    ) {
        $this->moduleHelper = $moduleHelper;
        $this->yesNo = $yesNo;
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