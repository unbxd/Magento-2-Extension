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
namespace Unbxd\ProductFeed\Controller\Adminhtml\Feed;

use Unbxd\ProductFeed\Controller\Adminhtml\ActionIndex;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Unbxd\ProductFeed\Model\Feed\FileManager as FeedFileManager;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Download
 * @package Unbxd\ProductFeed\Controller\Adminhtml\Feed
 */
class SearchDataDownload extends Action
{

    const DIR_FOR_DOWNLOAD = 'unbxd/download/search/';
    const STORE_PARAMETER = '_store';
    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $fileFactory;
    /**
     * @param \Magento\Framework\App\Action\Context            $context
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     */
    public function __construct(
        Context $context,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->fileFactory = $fileFactory;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }
    public function execute()
    {
        $storeId =  $this->getCurrentStoreId();
        $filePath = self::DIR_FOR_DOWNLOAD.'search_data'.self::STORE_PARAMETER.$storeId.'.csv';
        $downloadName = 'search_data'.self::STORE_PARAMETER.$storeId.'.csv';
        $content['type'] = 'filename';
        $content['value'] = $filePath;
        $content['rm'] = 0;
        $row  = $this->fileFactory->create($downloadName, $content, DirectoryList::VAR_DIR);
        return $row;
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
