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

    /**
     * Build category list related to product in specific format supported by Unbxd service
     * (ex.: /fashion|Fashion>/fashion/shoes|Shoes>/fashion/shoes/casual|Casual)
     *
     * @param $categoryData
     * @return array
     */
    public function buildCategoryList($categoryData)
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
            $urlPath = preg_replace('#/+#','/', $urlPath);
            if (!$urlPath) {
                continue;
            }

            if (strpos($urlPath, '/') !== false) {
                $pathData = explode('/', $urlPath);
            } else {
                $pathData = [$urlPath];
            }

            if (!empty($pathData)) {
                $path = '';
                $urlPart = '';
                foreach ($pathData as $urlKey) {
                    $key = array_search($urlKey, array_column($categoryData, 'url_key'));
                    $name = ucwords(trim(str_replace('-', ' ', strtolower($urlKey))));
                    if ($key && isset($categoryData[$key]['name'])) {
                        $name = trim($categoryData[$key]['name']);
                    }

                    $urlPart .= '/' . $urlKey;
                    $path .= sprintf('%s|%s>', $urlPart, $name);
                }
                $pathString = rtrim(trim($path, '>'), '/');
                $result[] = $pathString;

                $this->categoryCacheList[$categoryId] = $pathString;
            }
        }

        return array_values(array_unique($result, SORT_REGULAR));
    }
}