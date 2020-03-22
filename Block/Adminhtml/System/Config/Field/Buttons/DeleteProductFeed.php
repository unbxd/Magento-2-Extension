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
use Magento\Backend\Block\Template\Context;
use Unbxd\ProductFeed\Helper\Feed as FeedHelper;
use Unbxd\ProductFeed\Model\Feed\FileManager as FeedFileManager;
use Unbxd\ProductFeed\Model\Feed\FileManagerFactory as FeedFileManagerFactory;

/**
 * Class DeleteProductFeed
 * @package Unbxd\ProductFeed\Block\Adminhtml\System\Config\Field\Buttons
 */
class DeleteProductFeed extends AbstractButton
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
            'id' => 'unbxd_delete_product_feed',
            'label' => __('Delete'),
            'onclick' => "setLocation('{$this->getButtonUrl()}')",
        ];
        if ($this->isDisabled || !$this->isProductFeedAvailableForDelete()) {
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
            'unbxd_productfeed/feed/delete',
            [
                'store' => $this->_request->getParam('store')
            ]
        );
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function isProductFeedAvailableForDelete()
    {
        /** @var \Unbxd\ProductFeed\Model\Feed\FileManager $feedFileManager */
        $feedFileManager = $this->feedFileManagerFactory->create(
            [
                'subDir' => FeedFileManager::DEFAULT_SUB_DIR_FOR_DOWNLOAD,
                'store' => sprintf(
                    '%s%s',
                    FeedFileManager::STORE_PARAMETER,
                    $this->_request->getParam('store', $this->_storeManager->getStore()->getId())
                )
            ]
        );
        $feedFileManager->setIsConvertedToArchive(true);

        return $feedFileManager->isExist();
    }
}