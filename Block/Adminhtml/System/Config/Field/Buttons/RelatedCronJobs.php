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
 * Class RelatedCronJobs
 * @package Unbxd\ProductFeed\Block\Adminhtml\System\Config\Field\Buttons
 */
class RelatedCronJobs extends AbstractButton
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
            'id' => 'cron_jobs',
            'label' => __('Cron Jobs'),
            'data_attribute' => [
                'mage-init' => [
                    'Magento_Ui/js/form/button-adapter' => [
                        'actions' => [
                            [
                                'targetName' => 'unbxd_productfeed_cron_grid_modal.unbxd_productfeed_cron_grid_modal.cron_jobs_modal',
                                'actionName' => 'toggleModal'
                            ],
                            [
                                'targetName' => 'unbxd_productfeed_cron_grid_modal.unbxd_productfeed_cron_grid_modal.cron_jobs_modal.jobs_listing',
                                'actionName' => 'render'
                            ]
                        ]
                    ]
                ]
            ],
            'on_click' => '',
            'sort_order' => 10
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
     * @return string
     */
    public function getButtonUrl()
    {
        return $this->_urlBuilder->getUrl(
            'unbxd_productfeed/cron/check',
            [
                'store' => $this->_request->getParam('store')
            ]
        );
    }
}