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
namespace Unbxd\ProductFeed\Block\Adminhtml\System\Config\Field;

use Magento\Framework\UrlInterface;
use Unbxd\ProductFeed\Helper\Feed as FeedHelper;

/**
 * Class AbstractButton
 * @package Unbxd\ProductFeed\Block\Adminhtml\System\Config\Field
 */
abstract class AbstractButton extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var FeedHelper
     */
    protected $feedHelper;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * AbstractButton constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param FeedHelper $feedHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        FeedHelper $feedHelper,
        array $data = []
    ) {
        $this->urlBuilder = $context->getUrlBuilder();
        $this->feedHelper = $feedHelper;
        parent::__construct($context, $data);
    }

    /**
     * Set template
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('Unbxd_ProductFeed::system/config/field/button.phtml');
    }

    /**
     * Retrieve button html
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    abstract protected function getButtonHtml();

    /**
     * Retrieve button url
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    abstract protected function getButtonUrl();

    /**
     * Render button
     *
     * @param  \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        // remove scope label
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Return element html
     *
     * @param  \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return $this->_toHtml();
    }
}