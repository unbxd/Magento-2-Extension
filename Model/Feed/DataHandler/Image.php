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

use Magento\Catalog\Model\Product\Image as ProductImage;
use Magento\Catalog\Helper\Image as ImageHelper;
use Unbxd\ProductFeed\Helper\Data as HelperData;
use Magento\Catalog\Model\Product\Media\Config as MediaConfig;
use Magento\Framework\View\ConfigInterface as ViewConfigInterface;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Encryption\EncryptorInterface;

/**
 * Class Image
 * @package Unbxd\ProductFeed\Model\Feed\DataHandler
 */
class Image
{
    /**
     * Product image thumbnail role
     */
    const FEED_PRODUCT_THUMBNAIL_IMAGE_ROLE = 'feed_product_image_small';

    /**
     * Product image cache sub directory
     */
    const CACHE_SUB_DIR = 'cache';

    /**
     * XML path watermark image properties
     */
    const WATERMARK_PATH_IMAGE = 'design/watermark/%s_image';
    const WATERMARK_PATH_OPACITY = 'design/watermark/%s_imageOpacity';
    const WATERMARK_PATH_POSITION = 'design/watermark/%s_position';
    const WATERMARK_PATH_SIZE = 'design/watermark/%s_size';

    /**
     * Default quality value (for JPEG images only).
     *
     * @var int
     */
    protected $quality = 80;

    /**
     * @var bool
     */
    protected $keepAspectRatio = true;

    /**
     * @var bool
     */
    protected $keepFrame = true;

    /**
     * @var bool
     */
    protected $keepTransparency = true;

    /**
     * @var bool
     */
    protected $constrainOnly = true;

    /**
     * @var int[]
     */
    protected $backgroundColor = [255, 255, 255];

    /**
     * @var \Magento\Framework\Config\View
     */
    protected $configView;

    /**
     * @var ViewConfigInterface
     */
    protected $viewConfig;

    /**
     * @var ProductImage
     */
    private $productImage;

    /**
     * @var ImageHelper
     */
    private $imageHelper;

    /**
     * @var HelperData
     */
    private $helperData;

    /**
     * @var MediaConfig
     */
    private $catalogProductMediaConfig;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var null
     */
    private $defaultImagePlaceHolderUrl = null;

    /**
     * Local cache for image properties
     *
     * @var array
     */
    private $miscParams = [];

    /**
     * Local cache for image type
     *
     * @var null
     */
    private $type = null;

    /**
     * @var null
     */
    private $imageCacheSubDir = null;

    /**
     * Local cache for current working directory
     *
     * @var null
     */
    private $rootPath = null;

    /**
     * Image constructor.
     * @param ViewConfigInterface $viewConfig
     * @param ProductImage $productImage
     * @param ImageHelper $imageHelper
     * @param HelperData $helperData
     * @param MediaConfig $catalogProductMediaConfig
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        ViewConfigInterface $viewConfig,
        ProductImage $productImage,
        ImageHelper $imageHelper,
        HelperData $helperData,
        MediaConfig $catalogProductMediaConfig,
        EncryptorInterface $encryptor
    ) {
        $this->viewConfig = $viewConfig;
        $this->productImage = $productImage;
        $this->imageHelper = $imageHelper;
        $this->helperData = $helperData;
        $this->catalogProductMediaConfig = $catalogProductMediaConfig;
        $this->encryptor = $encryptor;
    }

    /**
     * Retrieve image type. Init if needed
     *
     * @return array|null
     */
    private function getType()
    {
        if (null == $this->type) {
            $this->type = $this->getImageAttribute('type');
        }

        return $this->type;
    }

    /**
     * @param null $attributeName
     * @return array|null
     */
    public function getImageAttribute($attributeName = null)
    {
        $attributes =
            $this->getConfigView()->getMediaAttributes(
                'Magento_Catalog',
                ImageHelper::MEDIA_TYPE_CONFIG_NODE,
                self::FEED_PRODUCT_THUMBNAIL_IMAGE_ROLE
            );

        return $attributeName ?
            (isset($attributes[$attributeName]) ? $attributes[$attributeName] : null)
            : $attributes;
    }

    /**
     * Retrieve config view. Init if needed
     *
     * @return \Magento\Framework\Config\View
     */
    private function getConfigView()
    {
        if (!$this->configView) {
            $this->configView = $this->viewConfig->getViewConfig();
        }

        return $this->configView;
    }


    /**
     * Retrieve misc params based on all image attributes. Init if needed
     *
     * @return array
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function getMiscParams()
    {
        if (empty($this->miscParams)) {
            $this->miscParams = [
                // if this parameter is included - incorrectly generating sub-dir hash, temporary commented
//                'image_type' => $this->getType(),
                'image_height' => $this->getImageAttribute('height'),
                'image_width' => $this->getImageAttribute('width'),
                'keep_aspect_ratio' => (
                    ($this->getImageAttribute('aspect_ratio') || $this->keepAspectRatio) ? '' : 'non'
                    ) . 'proportional',
                'keep_frame' => (
                    ($this->getImageAttribute('frame') || $this->keepFrame) ? '' : 'no'
                    ) . 'frame',
                'keep_transparency' => (
                    ($this->getImageAttribute('transparency') || $this->keepTransparency) ? '' : 'no'
                    ) . 'transparency',
                'constrain_only' => (
                    ($this->getImageAttribute('constrain') || $this->constrainOnly) ? 'do' : 'not'
                    ) . 'constrainonly',
                'background' => $this->rgbToString(
                    $this->getImageAttribute('background') ?: $this->backgroundColor),
                'angle' => null,
                'quality' => $this->quality,
            ];

            // if has watermark add watermark params to hash
            $this->setWatermarkProperties($this->miscParams);
        }

        return $this->miscParams;
    }

    /**
     * Set watermark properties
     *
     * @param array $miscParams
     * @return $this
     */
    private function setWatermarkProperties(array &$miscParams)
    {
        $type = $this->getType();
        $waterMarkImage = $this->helperData->getConfigValue(
            sprintf(self::WATERMARK_PATH_IMAGE, $type),
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if (!$waterMarkImage) {
            return $this;
        }

        $miscParams['watermark_file'] = $waterMarkImage;
        $miscParams['watermark_image_opacity'] =
            $this->helperData->getConfigValue(
                sprintf(self::WATERMARK_PATH_OPACITY, $type),
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        $miscParams['watermark_position'] =
            $this->helperData->getConfigValue(
                sprintf(self::WATERMARK_PATH_POSITION, $type),
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );

        $size = $this->parseSize(
            $this->helperData->getConfigValue(
                sprintf(self::WATERMARK_PATH_SIZE, $type),
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )
        );
        if ($size) {
            $miscParams['watermark_width'] = isset($size['width']) ? $size['width'] : 0;
            $miscParams['watermark_height'] = isset($size['height']) ? $size['height'] : 0;
        }

        return $this;
    }

    /**
     * Retrieve part of path based on misc params
     *
     * @return string
     */
    private function getMiscPath()
    {
        return $this->encryptor->hash(implode('_', $this->getMiscParams()), Encryptor::HASH_VERSION_MD5);
    }

    /**
     * Retrieve product image cache sub directory. Init if needed
     *
     * @return string|null
     */
    private function getImageCacheSubUrl()
    {
        if (null == $this->imageCacheSubDir) {
            $this->imageCacheSubDir = sprintf(
                '%s/%s/%s',
                $this->catalogProductMediaConfig->getBaseMediaUrl(),
                self::CACHE_SUB_DIR,
                $this->getMiscPath()
            );
        }

        return $this->imageCacheSubDir;
    }

    /**
     * Retrieve product image url
     *
     * @param $imagePath
     * @return string
     */
    public function getImageUrl($imagePath)
    {
        // try to retrieve cache url
        $url = $this->getImageCacheSubUrl() . $imagePath;
        $imageSubPath = substr($url, strpos($url, '/pub'));
        $imageRealPath = $this->getRootPath() . $imageSubPath;
        if (!file_exists($imageRealPath)) {
            // non cache url
            $url = $this->catalogProductMediaConfig->getMediaUrl($imagePath);
        }

        return $url;
    }

    /**
     * Retrieve default product image placeholder url. Init if needed
     *
     * @return null
     */
    public function getDefaultImagePlaceHolderUrl()
    {
        if (null == $this->defaultImagePlaceHolderUrl) {
            $this->defaultImagePlaceHolderUrl = $this->imageHelper->getDefaultPlaceholderUrl();
        }

        return $this->defaultImagePlaceHolderUrl;
    }

    /**
     * Convert array of 3 items (decimal r, g, b) to string of their hex values
     *
     * @param int[] $rgbArray
     * @return string
     */
    private function rgbToString($rgbArray)
    {
        $result = [];
        foreach ($rgbArray as $value) {
            if (null === $value) {
                $result[] = 'null';
            } else {
                $result[] = sprintf('%02s', dechex($value));
            }
        }

        return implode($result);
    }

    /**
     * Retrieve size from string
     *
     * @param string $string
     * @return array|bool
     */
    private function parseSize($string)
    {
        $size = explode('x', strtolower($string));
        if (sizeof($size) == 2) {
            return ['width' => $size[0] > 0 ? $size[0] : null, 'height' => $size[1] > 0 ? $size[1] : null];
        }

        return false;
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
}