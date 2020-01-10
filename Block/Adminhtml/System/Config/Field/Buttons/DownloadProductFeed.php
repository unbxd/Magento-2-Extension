<?php
/**
 * Copyright (c) 2019 Unbxd Inc.
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
use Magento\Backend\Block\Template\Context;
use Unbxd\ProductFeed\Helper\Feed as FeedHelper;
use Unbxd\ProductFeed\Model\Feed\FileManagerFactory as FeedFileManagerFactory;

/**
 * Class DownloadProductFeed
 * @package Unbxd\ProductFeed\Block\Adminhtml\System\Config\Field\Buttons
 */
class DownloadProductFeed extends AbstractButton
{
    /**
     * @var FeedFileManagerFactory
     */
    private $feedFileManagerFactory;

    /**
     * Check whether the button is disabled or not
     *
     * @var bool
     */
    private $isDisabled = false;

    /**
     * DownloadProductFeed constructor.
     * @param Context $context
     * @param FeedHelper $feedHelper
     * @param FeedFileManagerFactory $feedFileManagerFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        FeedHelper $feedHelper,
        FeedFileManagerFactory $feedFileManagerFactory,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $feedHelper,
            $data
        );
        $this->feedFileManagerFactory = $feedFileManagerFactory;
    }

    /**
     * @return mixed
     * @throws LocalizedException
     */
    public function getButtonHtml()
    {
        $buttonData = [
            'id' => 'unbxd_generate_product_fee',
            'label' => __('Download'),
            'onclick' => "setLocation('{$this->getButtonUrl()}')",
        ];
        if ($this->isDisabled || !$this->isProductFeedAvailableForDownload()) {
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
     * Get url for for download product feed action
     *
     * @return string
     */
    public function getButtonUrl()
    {
        return $this->_urlBuilder->getUrl(
            'unbxd_productfeed/feed/download',
            [
                'store' => $this->_request->getParam('store')
            ]
        );
    }

    /**
     * @return bool
     */
    private function isProductFeedAvailableForDownload()
    {
        /** @var \Unbxd\ProductFeed\Model\Feed\FileManager $feedFileManager */
        $feedFileManager = $this->feedFileManagerFactory->create();
        $feedFileManager->setIsConvertedToArchive(true);

        return $feedFileManager->isExist();
    }
}