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
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class DownloadProductFeed
 * @package Unbxd\ProductFeed\Block\Adminhtml\System\Config\Field\Buttons
 */
class DownloadSearchData extends AbstractButton
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
     * DownloadProductFeed constructor.
     * @param Context $context
     * @param FeedHelper $feedHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        FeedHelper $feedHelper,
        Filesystem $filesystem,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $feedHelper,
            $data
        );
        $this->storeManager  = $storeManager;
        $this->dir = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
    }

    /**
     * @return mixed
     * @throws LocalizedException
     */
    public function getButtonHtml()
    {
        $buttonData = [
            'id' => 'unbxd_generate_search_data',
            'label' => __('Download'),
            'onclick' => "setLocation('{$this->getButtonUrl()}')",
        ];
        if ($this->isDisabled || !$this->isExist()) {
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
     * Get url for for download search data action
     *
     * @return string
     */
    public function getButtonUrl()
    {
        return $this->_urlBuilder->getUrl(
            'unbxd_productfeed/feed/searchdatadownload',
            [
                'store' => $this->_request->getParam('store')
            ]
        );
    }

    public function getFilePath()
    {
        $storeId =  $this->getCurrentStoreId();
        $filePath = self::DIR_FOR_DOWNLOAD.'search_data'.self::STORE_PARAMETER.$storeId.'.csv';
        return $filePath;
    }

    public function isExist()
    {
        return $this->dir->isExist($this->getFilePath());
    }

    protected function getStore($store = null)
    {
        return $this->storeManager->getStore($store);
    }

    protected function getCurrentStoreId($store = null)
    {
        return $this->_request->getParam(Store::ENTITY, $this->getStore($store)->getId());
    }
}
