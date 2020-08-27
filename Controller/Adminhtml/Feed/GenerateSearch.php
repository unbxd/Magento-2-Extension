<?php
namespace Unbxd\ProductFeed\Controller\Adminhtml\Feed;

use Magento\Backend\App\Action;
use Magento\Framework\Exception\LocalizedException;
use Unbxd\ProductFeed\Model\BackgroundTaskManager;
use Unbxd\ProductFeed\Console\Command\Feed\SearchDownload;
use Unbxd\ProductFeed\Model\CacheManager;
use Magento\Store\Model\Store;
use Magento\Framework\App\RequestInterface;
use Magento\Backend\App\Action\Context;
use Magento\Store\Model\StoreManagerInterface;
use Unbxd\ProductFeed\Model\CacheManagerFactory;
use Unbxd\ProductFeed\Model\BackgroundTaskManagerFactory;
use Magento\Framework\Controller\ResultFactory;
use Unbxd\ProductFeed\Helper\Data as HelperData;



/**
 * Class GenerateSearch
 * @package Unbxd\ProductFeed\Controller\Adminhtml\Feed
 */
class GenerateSearch extends Action
{
    public function __construct(
        Context $context,
        RequestInterface $request,
        StoreManagerInterface $storeManager,
        CacheManagerFactory $cacheManagerFactory,
        BackgroundTaskManagerFactory $backgroundTaskManagerFactory,
        HelperData $helperData,
        ResultFactory $resultFactory
    ){
        parent::__construct($context);
        $this->_request = $request;
        $this->storeManager = $storeManager;
        $this->cacheManagerFactory = $cacheManagerFactory;
        $this->helperData = $helperData;
        $this->backgroundTaskManagerFactory = $backgroundTaskManagerFactory;
        $this->resultFactory = $resultFactory;
    }
    public function execute()
    {
        $this->flushCache();
        $this->setIsGeneratedForDownload(true);
        $isValid = $this->_isValidPostRequest();
        if (!$isValid) {
            $this->messageManager->addErrorMessage(__('Invalid request for search data csv generation.'));
            return $resultJson;
        }
        $storeId    = $this->getCurrentStoreId();
        $storeName  = $this->getStore($storeId)->getName();
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $responseContent = [];
        $resultJson->setData($responseContent);
        try {
            /** @var BackgroundTaskManager $backgroundTaskManager */
            $backgroundTaskManager = $this->backgroundTaskManagerFactory->create();
            $backgroundTaskManager->execute([SearchDownload::COMMAND], $storeId);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Unable to generate search data. Error %1', $e->getMessage()));
            return $resultJson;
            return;
        }
        $this->messageManager->addSuccessMessage(__('Search data generation for store with ID %1 (%2) was started.
            Generating may take some time depending on the fetching data. Once the data is generated
            you will be able to download it as an CSV format.', $storeId, $storeName)
        );
        return $resultJson;
    }

    protected function getCurrentStoreId($store = null)
    {
        return $this->_request->getParam(Store::ENTITY, $this->getStore($store)->getId());
    }
    protected function getStore($store = null)
    {
        return $this->storeManager->getStore($store);
    }

    private function flushCache()
    {
        /** @var CacheManager $cacheManager */
        $cacheManager = $this->cacheManagerFactory->create();
        $cacheManager->flushByTypes();
        return $this;
    }

    public function setIsGeneratedForDownload($status)
    {
        $this->helperData->updateConfigValue(SearchDownload::SEARCH_PATH_GENERATED_FOR_DOWNLOAD, $status);
        return $this;
    }

    protected function _isValidPostRequest()
    {
        $formKeyIsValid = $this->_formKeyValidator->validate($this->getRequest());
        $isAjax = $this->getRequest()->isAjax();
        $isPost = $this->getRequest()->isPost();

        return (bool) ($formKeyIsValid && $isAjax && $isPost);
    }
}
