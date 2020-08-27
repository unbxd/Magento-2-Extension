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
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Filesystem;

/**
 * Class Download
 * @package Unbxd\ProductFeed\Controller\Adminhtml\Feed
 */
class DeleteSearchData extends Action
{

    const DIR_FOR_DOWNLOAD = 'unbxd/download/search/';
    const STORE_PARAMETER = '_store';

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Filesystem $filesystem
    ) {
        $this->storeManager = $storeManager;
        $this->dir = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        parent::__construct($context);
    }
    public function execute()
    {
       $storeId =  $this->getCurrentStoreId();
       $default_file_path = self::DIR_FOR_DOWNLOAD.'search_data'.self::STORE_PARAMETER.$storeId.'.csv';
       $file_path = $this->dir->getAbsolutePath($default_file_path);
       $resultRedirect = $this->resultRedirectFactory->create();
       if (!$this->dir->isExist($default_file_path)) {
           $this->messageManager->addErrorMessage(
               __('There are no Search Data files for current store available for delete.')
           );
           return $resultRedirect->setRefererUrl();
       }
       try {
           $this->dir->getDriver()->deleteFile($file_path);
           $this->messageManager->addSuccessMessage(__('Search Data Csv files for current store were successfully deleted.'));
       } catch (\Exception $e) {
           $this->messageManager->addErrorMessage(__('Unable to delete search data files for current store.'));
       }
       return $resultRedirect->setRefererUrl();
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
