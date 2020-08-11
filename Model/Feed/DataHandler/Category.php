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

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     *
     * @var categoryFactory
     *
     */
    private $categoryFactory;

    public function __construct(LoggerInterface $logger, CategoryFactory $categoryFactory)
    {

        $this->logger = $logger->create("feed");
        $this->categoryFactory = $categoryFactory;

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
        $result = [];
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
            if (!$name || !$urlPath) {
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
                foreach ($pathData as $urlKey) {
                    $tempPath .= $urlKey;
                    $key = array_search($tempPath, array_column($categoryData, 'url_path'));
                    //$name = ucwords(trim(str_replace('-', ' ', strtolower($urlKey))));
                    if ($key !== false && isset($categoryData[$key]['name'])) {
                        $name = trim($categoryData[$key]['name']);
                    } else {
                        try {
                            if (!in_array($tempPath, $this->$missingCategoryPath)) {
                                $category = $this->categoryFactory->create()->setStoreId($store)->loadByAttribute('url_path', $tempPath);
                            } else {
                                $category = [];
                            }
                            if (!empty($category)) {
                                $name = $category->getName();
                                $this->logger->info("Setting category name -" . $name . " with category ID " . $category->getId() . " & path -" . $tempPath . " for entityID- " . $entity_id);
                            } else {
                                $this->logger->error("Unable to find category path -" . $tempPath . " for entityID- " . $entity_id);
                                $this->$missingCategoryPath[] = $tempPath;
                                $skipRecord = true;
                                break;
                            }

                        } catch (\Exception $e) {
                            $this->logger->error("Encountered exception while fetching category -" . $tempPath . " for entityID- " . $entity_id . " with error " . $e->getMessage() . " -stack-" . $e->getTraceAsString());
                            $skipRecord = true;
                            break;

                        }
                    }

                    $urlPart .= '/' . $urlKey;
                    $path .= sprintf('%s|%s>', $urlPart, $name);
                    $tempPath .= '/';
                }
                if (!$skipRecord) {
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
    }
}
