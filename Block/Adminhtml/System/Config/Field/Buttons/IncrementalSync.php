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
namespace Unbxd\ProductFeed\Block\Adminhtml\System\Config\Field\Buttons;

use Unbxd\ProductFeed\Block\Adminhtml\System\Config\Field\AbstractButton;

/**
 * Class IncrementalSync
 * @package Unbxd\ProductFeed\Block\Adminhtml\System\Config\Field\Buttons
 */
class IncrementalSync extends AbstractButton
{
    /**
     * Check whether the button is disabled or not
     *
     * @var bool
     */
    private $isDisabled = true;

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getButtonHtml()
    {
        $buttonData = [
            'id' => 'unbxd_incremental_sync',
            'label' => __('Synchronize')
        ];

        if ($this->feedHelper->isFullCatalogSynchronized()) {
            $this->isDisabled = false;
        }

        if ($this->isDisabled) {
            $buttonData['disabled'] = 'disabled';
        }

        $button = $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Button::class
        )->setData(
            $buttonData
        );

        return $button->toHtml();
    }

    /**
     * Get url for for incremental sync action
     *
     * @return string
     */
    public function getButtonUrl()
    {
        return $this->_urlBuilder->getUrl(
            'unbxd_productfeed/feed/incremental',
            [
                'store' => $this->_request->getParam('store')
            ]
        );
    }
}