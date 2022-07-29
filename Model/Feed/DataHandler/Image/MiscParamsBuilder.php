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
namespace Unbxd\ProductFeed\Model\Feed\DataHandler\Image;

use Magento\Catalog\Model\Product\Image;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\ConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Unbxd\ProductFeed\Helper\Data as HelperData;

/**
 * Class MiscParamBuilder
 * @package Unbxd\ProductFeed\Model\Feed\DataHandler\Image
 */
class MiscParamsBuilder
{
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
    protected $defaultQuality = 80;

    /**
     * @var array
     */
    private $defaultBackground = [255, 255, 255];

    /**
     * @var int|null
     */
    private $defaultAngle = null;

    /**
     * @var bool
     */
    private $defaultKeepAspectRatio = true;

    /**
     * @var bool
     */
    private $defaultKeepTransparency = true;

    /**
     * @var bool
     */
    private $defaultConstrainOnly = true;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ConfigInterface
     */
    private $viewConfig;

    /**
     * @var HelperData
     */
    private $helperData;

    /**
     * MiscParamsBuilder constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param ConfigInterface $viewConfig
     * @param HelperData $helperData
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ConfigInterface $viewConfig,
        HelperData $helperData
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->viewConfig = $viewConfig;
        $this->helperData = $helperData;
    }

    /**
     * Build image misc parameters. Use to forming images cache directory
     *
     * @param array $properties
     * @return array
     */
    public function build(array $properties)
    {
        $type = isset($properties['type']) ? $properties['type'] : null;
        $miscParams = [
            'image_height' => isset($properties['height']) ? $properties['height'] : null,
            'image_width' => isset($properties['width']) ? $properties['width'] : null,
        ];
        $defaultValues = $this->overwriteDefaultValues($properties);
        $this->applyWatermarkProperties($miscParams, $type);
        $miscParams = array_merge($miscParams, $defaultValues);
        $miscParams = $this->applyConvertStrategy($miscParams);

        return $miscParams;
    }

    /**
     * Overwrite default values
     *
     * @param array $properties
     * @return array
     */
    private function overwriteDefaultValues(array $properties)
    {
        $frame = isset($properties['frame']) ? $properties['frame'] : $this->hasDefaultFrame();
        $constrain = isset($properties['constrain']) ? $properties['constrain'] : $this->defaultConstrainOnly;
        $aspectRatio = isset($properties['aspect_ratio']) ? $properties['aspect_ratio'] : $this->defaultKeepAspectRatio;
        $transparency = isset($properties['transparency']) ? $properties['transparency'] : $this->defaultKeepTransparency;
        $background = isset($properties['background']) ? $properties['background'] : $this->defaultBackground;
        $angle = isset($properties['angle']) ? $properties['angle'] : $this->defaultAngle;
        // use string path instead of constant, as constant doesn't exist in older Magento versions
        $quality = (int) $this->scopeConfig->getValue('system/upload_configuration/jpeg_quality');
        if (!$quality) {
            $quality = $this->defaultQuality;
        }

        return [
            'background' => (array) $background,
            'angle' => $angle,
            'quality' => $quality,
            'keep_aspect_ratio' => (bool) $aspectRatio,
            'keep_frame' => (bool) $frame,
            'keep_transparency' => (bool) $transparency,
            'constrain_only' => (bool) $constrain,
        ];
    }

    /**
     * Set watermark properties
     *
     * @param array $miscParams
     * @param $type
     * @return $this
     */
    private function applyWatermarkProperties(array &$miscParams, $type)
    {
        $image = $this->helperData->getConfigValue(
            sprintf(self::WATERMARK_PATH_IMAGE, $type),
            ScopeInterface::SCOPE_STORE
        );
        if (!$image) {
            return $this;
        }

        $miscParams['watermark_file'] = $image;
        $miscParams['watermark_image_opacity'] = $this->helperData->getConfigValue(
                sprintf(self::WATERMARK_PATH_OPACITY, $type),
                ScopeInterface::SCOPE_STORE
            );
        $miscParams['watermark_position'] = $this->helperData->getConfigValue(
                sprintf(self::WATERMARK_PATH_POSITION, $type),
                ScopeInterface::SCOPE_STORE
            );

        $size = $this->parseSize(
            $this->helperData->getConfigValue(
                sprintf(self::WATERMARK_PATH_SIZE, $type),
                ScopeInterface::SCOPE_STORE
            )
        );
        if ($size) {
            $miscParams['watermark_width'] = isset($size['width']) ? $size['width'] : 0;
            $miscParams['watermark_height'] = isset($size['height']) ? $size['height'] : 0;
        }

        return $this;
    }

    /**
     * Retrieve size from string
     *
     * @param string $string
     * @return array|bool
     */
    private function parseSize($string)
    {
        $size = explode('x', strtolower($string??''));
        if (sizeof($size) == 2) {
            return ['width' => $size[0] > 0 ? $size[0] : null, 'height' => $size[1] > 0 ? $size[1] : null];
        }

        return false;
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
     * Get frame from product_image_white_borders
     *
     * @return bool
     */
    private function hasDefaultFrame()
    {
        return (bool) $this->viewConfig->getViewConfig(['area' => \Magento\Framework\App\Area::AREA_FRONTEND])
            ->getVarValue('Magento_Catalog', 'product_image_white_borders');
    }

    /**
     * The purpose of this method correctly form the misc parameters
     * for the correct formation of the subdirectory hash for product images,
     * depending on magento version.
     *
     * @param array $miscParams
     * @return array
     */
    private function applyConvertStrategy(array $miscParams)
    {
        if (class_exists(\Magento\Catalog\Model\View\Asset\Image::class)) {
            if (
                method_exists(
                    \Magento\Catalog\Model\View\Asset\Image::class,
                    'convertToReadableFormat'
                )
            ) {
                // for Magento version >= 2.3.0
                return $this->convertToReadableFormat($miscParams);
            }
        }
        // for Magento version < 2.3.0
        return $this->convertToStandardFormat($miscParams);
    }

    /**
     * Converting image misc parameters into a string representation
     *
     * @param array $miscParams
     * @return array
     */
    private function convertToReadableFormat(array $miscParams)
    {
        $miscParams['image_height'] = 'h:' . ($miscParams['image_height'] ? $miscParams['image_height'] : 'empty');
        $miscParams['image_width'] = 'w:' . ($miscParams['image_width'] ? $miscParams['image_width'] : 'empty');
        $miscParams['quality'] = 'q:' . ($miscParams['quality'] ? $miscParams['quality'] : 'empty');
        $miscParams['angle'] = 'r:' . ($miscParams['angle'] ? $miscParams['angle'] : 'empty');
        $miscParams['keep_aspect_ratio'] = (!empty($miscParams['keep_aspect_ratio']) ? '' : 'non') . 'proportional';
        $miscParams['keep_frame'] = (!empty($miscParams['keep_frame']) ? '' : 'no') . 'frame';
        $miscParams['keep_transparency'] = (!empty($miscParams['keep_transparency']) ? '' : 'no') . 'transparency';
        $miscParams['constrain_only'] = (!empty($miscParams['constrain_only']) ? 'do' : 'not') . 'constrainonly';
        $miscParams['background'] = !empty($miscParams['background'])
            ? 'rgb' . implode(',', $miscParams['background'])
            : 'nobackground';

        // for proper hash generating apply right order
        $order = [
            'image_height',
            'image_width',
            'background',
            'angle',
            'quality',
            'keep_aspect_ratio',
            'keep_frame',
            'keep_transparency',
            'constrain_only'
        ];
        uksort($miscParams, function ($a, $b) use ($order) {
            $posA = array_search($a, $order);
            $posB = array_search($b, $order);
            return $posA - $posB;
        });

        return $miscParams;
    }

    /**
     * Converting specific image misc params into a string representation
     *
     * @param array $miscParams
     * @return array
     */
    private function convertToStandardFormat(array $miscParams)
    {
        $miscParams['keep_aspect_ratio'] = (!empty($miscParams['keep_aspect_ratio']) ? '' : 'non') . 'proportional';
        $miscParams['keep_frame'] = (!empty($miscParams['keep_frame']) ? '' : 'no') . 'frame';
        $miscParams['keep_transparency'] = (!empty($miscParams['keep_transparency']) ? '' : 'no') . 'transparency';
        $miscParams['constrain_only'] = (!empty($miscParams['constrain_only']) ? 'do' : 'not') . 'constrainonly';
        $miscParams['background'] = !empty($miscParams['background'])
            ? $this->rgbToString($miscParams['background'])
            : 'nobackground';

        // for proper hash generating apply right order
        $order = [
            'image_height',
            'image_width',
            'keep_aspect_ratio',
            'keep_frame',
            'keep_transparency',
            'constrain_only',
            'background',
            'angle',
            'quality'
        ];
        uksort($miscParams, function ($a, $b) use ($order) {
            $posA = array_search($a, $order);
            $posB = array_search($b, $order);
            return $posA - $posB;
        });

        return $miscParams;
    }
}