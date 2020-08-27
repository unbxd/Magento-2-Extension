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
use Magento\Framework\Exception\LocalizedException;

/**
 * Class GenerateSearch
 * @package Unbxd\ProductFeed\Block\Adminhtml\System\Config\Field\Buttons
 */
class GenerateSearchData extends AbstractButton
{
    const DIR_FOR_DOWNLOAD = 'unbxd/download/search/';
    const STORE_PARAMETER = '_store';
    /**
     * Check whether the button is disabled or not
     *
     * @var bool
     */
    private $isDisabled = false;

    /**
     * @return mixed
     * @throws LocalizedException
     */
    public function getButtonHtml()
    {
        $buttonData = [
            'id' => 'unbxd_generate_csv_data',
            'label' => __('Generate')
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
     * Get url for for generate product feed action
     *
     * @return string
     */
    public function getButtonUrl()
    {
        return $this->_urlBuilder->getUrl(
            'unbxd_productfeed/feed/generatesearch',
            [
                'store' => $this->_request->getParam('store')
            ]
        );
    }
}
