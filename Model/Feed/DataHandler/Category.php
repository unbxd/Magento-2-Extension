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
namespace Unbxd\ProductFeed\Model\Feed\DataHandler;

use Magento\Catalog\Model\CategoryFactory;
use Unbxd\ProductFeed\Logger\LoggerInterface;
use Unbxd\ProductFeed\Helper\Data as HelperData;
use Magento\Store\Model\StoreManager as StoreManager;

/**
 * Class Category
 * @package Unbxd\ProductFeed\Model\Feed\DataHandler
 */
class Category
{
    /**
     * Local cache for category list data in specific format
     *
     * @var array
     */
    private $categoryCacheList = [];

    private $missingCategoryPath = [];

    private $skippedCategoryCacheList = [];

    private $rootCategoryId;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var HelperData
     */
    private $helperData;

    private $storeManager;

    /**
     *
     * @var categoryFactory
     *
     */
    private $categoryFactory;

    public function __construct(LoggerInterface $logger, CategoryFactory $categoryFactory,HelperData $helperData,StoreManager $storeManager)
    {

        $this->logger = $logger->create("feed");
        $this->categoryFactory = $categoryFactory;
        $this->helperData = $helperData;
        $this->storeManager = $storeManager;

    }

    /**
     * Build category list related to product in specific format supported by Unbxd service
     * (ex.: /fashion|Fashion>/fashion/shoes|Shoes>/fashion/shoes/casual|Casual)
     *
     * @param $categoryData
     * @return array
     */
    public function buildCategoryList($categoryData, $store, $entity_id)
    {
        if ($this->helperData->useCategoryID($store)){
            return $this->buildCategoryIdList($categoryData,$store,$entity_id);
        }else{
            return $this->buildCategoryPathList($categoryData,$store,$entity_id);
        }
    }

    private function buildCategoryIdList($categoryData,$store,$entity_id)
    {
        if (!$this->rootCategoryId){
            $this->rootCategoryId = $this->storeManager->getStore($store)->getRootCategoryId();
        }
        $result = [];
        $retainInactiveCategory = $this->helperData->retainInActiveCategories($store);
        foreach ($categoryData as $data) {
            $categoryId = isset($data['category_id']) ? (int) $data['category_id'] : null;
            // try to retrieve category list data from cache
            if (isset($this->categoryCacheList[$categoryId])) {
                $result[] = $this->categoryCacheList[$categoryId];
                continue;
            }else if(in_array($categoryId,$this->skippedCategoryCacheList)){
                continue;
            }
            $name = isset($data['name']) ? (string) trim($data['name']) : null;
            $urlPath = isset($data['id_path'])
            ? (string) trim($data['id_path'], '/') : null;
            
            if (!$name ||  !$urlPath) {
                continue;
            }
            if (substr($urlPath,0,1) != "/") {
                $urlPath = "/".$urlPath;
            }
            if (!$this->helperData->retainRootCategory($store)){
            $urlPath = substr(strstr($urlPath,'/'.$this->rootCategoryId.'/'),strlen('/'.$this->rootCategoryId.'/'));
            }
            // remove double slashes from path if any
            $urlPath = preg_replace('#/+#', '/', $urlPath);
            if (!$urlPath) {
                continue;
            }

            if (strpos($urlPath, '/') !== false) {
                $pathData = explode('/', $urlPath);
            } else {
                $pathData = [$urlPath];
            }
            $skipRecord = false;
            if (!empty($pathData)) {
                $path = '';
                $urlPart = '';
                $tempPath = '';
                foreach ($pathData as $urlKey) {
                    $name=null;
                    $tempPath .= $urlKey;
                    $key = array_search($urlKey, array_column($categoryData, 'category_id'));
                    //$name = ucwords(trim(str_replace('-', ' ', strtolower($urlKey))));
                    if ($key !== false && isset($categoryData[$key]['name']) ) {
                        if((isset($categoryData[$key]['is_active']) && $categoryData[$key]['is_active']) || $retainInactiveCategory){
                            $name = trim($categoryData[$key]['name']);
                        }else{
                            $skipRecord = true;
                            break;
                        }
                    } else {
                        try {
                            if (!in_array($urlKey, $this->missingCategoryPath)) {
                                // add cache to get categoryName without reloading
                                $this->logger->debug("Load Category by urlKey " . $urlKey . " for entityID- " . $entity_id);
                                $category = $this->categoryFactory->create()->setStoreId($store)->load($urlKey);
                            } else {
                                $category = [];
                                $skipRecord = true;
                                break;
                            }
                            if (!empty($category)) {
                                if($category->getIsActive() || $retainInactiveCategory){
                                    $name = $category->getName();
                                }else{
                                    $skipRecord = true;
                                    $this->missingCategoryPath[] = $urlKey;
                                    break;
                                }
                            } else {
                                $this->logger->error("Unable to find category path -" . $urlKey . " for entityID- " . $entity_id);
                                $this->missingCategoryPath[] = $urlKey;
                                $skipRecord = true;
                                break;
                            }

                        } catch (\Exception $e) {
                            $this->logger->error("Encountered exception while fetching category -" . $urlKey . " for entityID- " . $entity_id . " with error " . $e->getMessage() . " -stack-" . $e->getTraceAsString());
                            $skipRecord = true;
                            break;

                        }
                    }
                    if($name){
                    $urlPart =  $urlKey;
                    $path .= $urlPart.'|'. $name.'>';
                    }
                    $tempPath .= '/';
                }
                if (!$skipRecord && $path) {
                    $pathString = rtrim(trim($path, '>'), '/');
                    $result[] = $pathString;

                    $this->categoryCacheList[$categoryId] = $pathString;
                }else{
                    $this->skippedCategoryCacheList[] = $categoryId;
                }
            }
        }

        return array_values(array_unique($result, SORT_REGULAR));

    }

    private function buildCategoryPathList($categoryData,$store,$entity_id){
        $result = [];
        $retainInactiveCategory = $this->helperData->retainInActiveCategories($store);
        foreach ($categoryData as $data) {
            $categoryId = isset($data['category_id']) ? (int) $data['category_id'] : null;
            // try to retrieve category list data from cache
            if (isset($this->categoryCacheList[$categoryId])) {
                $result[] = $this->categoryCacheList[$categoryId];
                continue;
            }
            $name = isset($data['name']) ? (string) trim($data['name']) : null;
            $urlPath = isset($data['url_path'])
            ? (string) trim($data['url_path'], '/')
            : (isset($data['url_key']) ? (string) trim($data['url_key'], '/') : null);
            if (!$name ||  !$urlPath) {
                continue;
            }

            // remove double slashes from path if any
            $urlPath = preg_replace('#/+#', '/', $urlPath);
            if (!$urlPath) {
                continue;
            }

            if (strpos($urlPath, '/') !== false) {
                $pathData = explode('/', $urlPath);
            } else {
                $pathData = [$urlPath];
            }
            $skipRecord = false;
            if (!empty($pathData)) {
                $path = '';
                $urlPart = '';
                $tempPath = '';
                $categoryLevels = count($pathData);
                $categoryLevelIndex = 0;
                foreach ($pathData as $urlKey) {
                    $categoryLevelIndex++;
                    $tempPath .= $urlKey;
                    $key = array_search($tempPath, array_column($categoryData, 'url_path'));
                    //$name = ucwords(trim(str_replace('-', ' ', strtolower($urlKey))));
                    if ($key !== false && isset($categoryData[$key]['name'])){
                        if((isset($categoryData[$key]['is_active']) && $categoryData[$key]['is_active']) || $retainInactiveCategory){
                        $name = trim($categoryData[$key]['name']);
                        }else{
                            $this->logger->debug("Skipping disabled category   with category ID " . $key . " for entityID- " . $entity_id);
                                $skipRecord = true;
                                break;

                        }
                    } else {
                        try {
                            if (!in_array($tempPath, $this->missingCategoryPath)) {
                                $category = $this->categoryFactory->create()->setStoreId($store)->loadByAttribute('url_path', $tempPath);
                                if(empty($category) && isset($data['id_path'])){
                                    $urlKeyList = explode("/",trim($data['id_path']));
                                    $categoryLookupPath = implode("/",array_slice($urlKeyList,0,(count($urlKeyList)-($categoryLevels-$categoryLevelIndex))));
                                    $category = $this->categoryFactory->create()->setStoreId($store)->loadByAttribute('path', $categoryLookupPath);
                                }
                            } else {
                                $category = [];
                            }
                            if (!empty($category)) {
                                if($category->getIsActive() || $retainInactiveCategory){
                                    $name = $category->getName();
                                    $this->logger->debug("Setting category name -" . $name . " with category ID " . $category->getId() . " & path -" . $tempPath . " for entityID- " . $entity_id);
                                }else{
                                    $name="";
                                    $skipRecord = true;
                                    break;
                                    $this->logger->debug("Skipping disabled category   with category ID " . $category->getId() . " for entityID- " . $entity_id);
                                }
                            } else {
                                $this->logger->error("Unable to find category path -" . $tempPath . " for entityID- " . $entity_id);
                                $this->missingCategoryPath[] = $tempPath;
                                $skipRecord = true;
                                break;
                            }

                        } catch (\Exception $e) {
                            $this->logger->error("Encountered exception while fetching category -" . $tempPath . " for entityID- " . $entity_id . " with error " . $e->getMessage() . " -stack-" . $e->getTraceAsString());
                            $skipRecord = true;
                            break;

                        }
                    }

                    if($name){
                    $urlPart .= '/' . $urlKey;
                    $path .=  $urlPart.'|'. $name . '>';
                    }
                    $tempPath .= '/';
                }
                if (!$skipRecord && $path) {
                    $pathString = rtrim(trim($path, '>'), '/');
                    $result[] = $pathString;

                    $this->categoryCacheList[$categoryId] = $pathString;
                }
            }
        }

        return array_values(array_unique($result, SORT_REGULAR));
    }

    public function reset()
    {
        $this->categoryCacheList = [];
        $this->skippedCategoryCacheList = [];
    }
}
