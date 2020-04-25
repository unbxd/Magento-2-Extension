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

use Unbxd\ProductFeed\Model\Feed\DataHandler\Image\MiscParamsBuilder;
use Magento\Catalog\Helper\Image as ImageHelper;
use Unbxd\ProductFeed\Helper\Data as HelperData;
use Magento\Catalog\Model\Product\Media\Config as MediaConfig;
use Magento\Framework\View\ConfigInterface as ViewConfigInterface;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Class Image
 * @package Unbxd\ProductFeed\Model\Feed\DataHandler
 */
class Image
{
    /**
     * Feed product image roles prefix
     */
    const FEED_PRODUCT_IMAGE_PREFIX = 'unbxd_feed_product';

    /**
     * @var MediaConfig
     */
    private $catalogProductMediaConfig;

    /**
     * @var HelperData
     */
    private $helperData;

    /**
     * @var MiscParamsBuilder
     */
    private $miscParamsBuilder;

    /**
     * @var \Magento\Framework\Config\View|null
     */
    private $configView = null;

    /**
     * @var ViewConfigInterface
     */
    protected $viewConfig;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * Product image cache sub directory
     */
    private $cacheSubDir;

    /**
     * Local cache for image properties
     *
     * @var array
     */
    private $miscParams = [];

    /**
     * Local cache for images cache URL by image type
     *
     * @var array
     */
    private $cachedUrl = [];

    /**
     * Local cache for current working directory
     *
     * @var null
     */
    private $rootPath = null;

    /**
     * Image constructor.
     * @param MediaConfig $catalogProductMediaConfig
     * @param HelperData $helperData
     * @param ViewConfigInterface $viewConfig
     * @param MiscParamsBuilder $miscParamsBuilder
     * @param EncryptorInterface $encryptor
     * @param $cacheSubDir
     */
    public function __construct(
        MediaConfig $catalogProductMediaConfig,
        HelperData $helperData,
        ViewConfigInterface $viewConfig,
        MiscParamsBuilder $miscParamsBuilder,
        EncryptorInterface $encryptor,
        $cacheSubDir
    ) {
        $this->catalogProductMediaConfig = $catalogProductMediaConfig;
        $this->helperData = $helperData;
        $this->viewConfig = $viewConfig;
        $this->miscParamsBuilder = $miscParamsBuilder;
        $this->encryptor = $encryptor;
        $this->cacheSubDir = $cacheSubDir;
    }

    /**
     * @return array
     */
    public static function getMediaAttributes()
    {
        return ['image', 'small_image', 'thumbnail', 'swatch_image'];
    }

    /**
     * @param $type
     * @return mixed|null
     */
    private function getDefaultImageIdByType($type)
    {
        return sprintf('%s_%s', self::FEED_PRODUCT_IMAGE_PREFIX, $type);
    }

    /**
     * Retrieve config view. Init if needed
     *
     * @return \Magento\Framework\Config\View
     */
    private function getConfigView()
    {
        if (null == $this->configView) {
            $this->configView = $this->viewConfig->getViewConfig();
        }
        return $this->configView;
    }

    /**
     * @param $type
     * @param null $store
     * @param null $attributeName
     * @param null $default
     * @return array|mixed|null
     */
    public function getMediaAttribute($type, $store = null, $attributeName = null, $default = null)
    {
        $imageType = $this->helperData->getImageByType($type, $store);
        $imageType = explode('|', strtolower($imageType), 2);

        $attributes = $this->getConfigView()->getMediaAttributes(
                'Magento_Catalog',
                ImageHelper::MEDIA_TYPE_CONFIG_NODE,
                $imageType[0]
            );
        if (empty($attributes)) {
            // doesn't pick up image attributes from module/theme?
            if (isset($imageType[1])) {
                $attributes = [
                    'type' => $type,
                    'frame' => (bool) !strpos($imageType[0], 'no_frame')
                ];
                // size parameters are provided - try to add height and width
                $size = explode('x', strtolower($imageType[1]), 2);
                if (sizeof($size) == 2) {
                    $attributes['height'] = $size[0] > 0 ? $size[0] : null;
                    $attributes['width'] = $size[1] > 0 ? $size[1] : null;
                }
            } else {
                // try to retrieve default values declared in etc/view.xml
                $attributes = $this->getConfigView()->getMediaAttributes(
                    'Magento_Catalog',
                    ImageHelper::MEDIA_TYPE_CONFIG_NODE,
                    $this->getDefaultImageIdByType($type)
                );
            }
        }

        return $attributeName ?
            (isset($attributes[$attributeName]) ? $attributes[$attributeName] : $default)
            : $attributes;
    }

    /**
     * Retrieve misc params based on all image attributes. Init if needed
     *
     * @param $type
     * @param null $store
     * @return mixed
     */
    private function getMiscParams($type, $store = null)
    {
        if (!isset($this->miscParams[$type])) {
            $this->miscParams[$type] = $this->miscParamsBuilder->build($this->getMediaAttribute($type, $store));
        }
        return $this->miscParams[$type];
    }

    /**
     * Retrieve part of image path based on misc params
     *
     * @param $type
     * @param null $store
     * @return string
     */
    private function getMiscPath($type, $store = null)
    {
        return $this->encryptor->hash(
            implode('_', $this->getMiscParams($type, $store)),
            Encryptor::HASH_VERSION_MD5
        );
    }

    /**
     * Retrieve product image cache URL. Init if needed
     *
     * @param $type
     * @param null $store
     * @return mixed
     */
    private function getCachedUrl($type, $store = null)
    {
        if (!isset($this->cacheSubDirs[$type])) {
            $this->cachedUrl[$type] = sprintf(
                '%s/%s/%s',
                $this->catalogProductMediaConfig->getBaseMediaUrl(),
                $this->cacheSubDir,
                $this->getMiscPath($type, $store)
            );
        }
        return $this->cachedUrl[$type];
    }

    /**
     * Retrieve current working root directory. Init if needed
     *
     * @return bool|string|null
     */
    private function getRootPath()
    {
        if (null == $this->rootPath) {
            $rootPath = getcwd();
            if (!$rootPath) {
                $rootPath = substr(__DIR__, 0, strpos(__DIR__, '/app'));
            }
            $this->rootPath = $rootPath;
        }
        return $this->rootPath;
    }

    /**
     * Build real filepath, to check if image cached file is exist
     *
     * @param $url
     * @return string
     */
    private function getCachedImageRealPath($url)
    {
        $pointDirectory = DirectoryList::PUB;
        $isPubDirectoryOmit = false;
        if (!strpos($url, $pointDirectory)) {
            // pub directory can be omit in url due to store configuration
            $pointDirectory = DirectoryList::MEDIA;
            $isPubDirectoryOmit = true;
        }
        // get absolute path to image file
        $cachedSubPath = substr($url, strpos($url, DIRECTORY_SEPARATOR . $pointDirectory));
        if ($isPubDirectoryOmit) {
            // added pub directory to result path
            return sprintf('%s%s/%s', $this->getRootPath(), DirectoryList::PUB, $cachedSubPath);
        }
        return sprintf('%s%s', $this->getRootPath(), $cachedSubPath);
    }

    /**
     * Retrieve product image url
     *
     * @param $imagePath
     * @param $imageType
     * @param null $store
     * @return string
     */
    public function getImageUrl($imagePath, $imageType, $store = null)
    {
        // check if provided value already as url
        if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
            return $imagePath;
        }
        $url = $this->catalogProductMediaConfig->getMediaUrl($imagePath);
        if ($this->helperData->useCachedProductImages($store)) {
            $cachedUrl = sprintf('%s%s', $this->getCachedUrl($imageType, $store), $imagePath);
            $cachedImageRealPath = $this->getCachedImageRealPath($cachedUrl);
            $url = file_exists($cachedImageRealPath) ? $cachedUrl : $url;
        }
        return $url;
    }
}