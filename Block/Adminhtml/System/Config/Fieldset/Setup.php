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
namespace Unbxd\ProductFeed\Block\Adminhtml\System\Config\Fieldset;

/**
 * Class Setup
 * @package Unbxd\ProductFeed\Block\Adminhtml\System\Config\Fieldset
 */
class Setup extends AbstractFieldset
{
    /**
     * @var string
     */
    protected $_template = 'Unbxd_ProductFeed::system/config/fieldset/setup.phtml';

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return mixed
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $html = '';
        if ($element->getData('group')['id'] == 'setup_header') {
            $html = $this->toHtml();
        }
        return $html;
    }
}
