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
 * Class FullSync
 * @package Unbxd\ProductFeed\Block\Adminhtml\System\Config\Field\Buttons
 */
class FullSync extends AbstractButton
{
    /**
     * Check whether the button is disabled or not
     *
     * @var bool
     */
    private $isDisabled = false;

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getButtonHtml()
    {
        $buttonData = [
            'id' => 'unbxd_full_sync',
            'label' => __('Synchronize')
        ];
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
     * Get url for for full sync action
     *
     * @return string
     */
    public function getButtonUrl()
    {
        return $this->_urlBuilder->getUrl(
            'unbxd_productfeed/feed/full',
            [
                'store' => $this->_request->getParam('store')
            ]
        );
    }
}