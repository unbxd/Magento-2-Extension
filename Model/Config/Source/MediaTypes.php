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
namespace Unbxd\ProductFeed\Model\Config\Source;

use Magento\Framework\App\Area;
use Magento\Framework\Option\ArrayInterface;
use Magento\Framework\View\ConfigInterface as ViewConfigInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Theme\Model\Config\Customization as ThemeCustomizationConfig;
use Magento\Theme\Model\ResourceModel\Theme\Collection;

/**
 * Class MediaTypes
 * @package Unbxd\ProductFeed\Model\Config\Source
 */
class MediaTypes implements ArrayInterface
{
    /**
     * @var ViewConfigInterface
     */
    protected $viewConfig;

    /**
     * @var ThemeCustomizationConfig
     */
    private $themeCustomizationConfig;

    /**
     * @var Collection
     */
    private $themeCollection;

    /**
     * @var array
     */
    private $mediaTypes = [];

    /**
     * MediaTypes constructor.
     * @param ViewConfigInterface $viewConfig
     * @param ThemeCustomizationConfig $themeCustomizationConfig
     * @param Collection $themeCollection
     */
    public function __construct(
        ViewConfigInterface $viewConfig,
        ThemeCustomizationConfig $themeCustomizationConfig,
        Collection $themeCollection
    ) {
        $this->viewConfig = $viewConfig;
        $this->themeCustomizationConfig = $themeCustomizationConfig;
        $this->themeCollection = $themeCollection;
    }

    /**
     * Search the current theme
     * @return array
     */
    private function getThemesInUse()
    {
        $themesInUse = [];
        $registeredThemes = $this->themeCollection->loadRegisteredThemes();
        $storesByThemes = $this->themeCustomizationConfig->getStoresByThemes();
        $keyType = is_integer(key($storesByThemes)) ? 'getId' : 'getCode';
        foreach ($registeredThemes as $registeredTheme) {
            if (array_key_exists($registeredTheme->$keyType(), $storesByThemes)) {
                $themesInUse[] = $registeredTheme;
            }
        }
        return $themesInUse;
    }

    /**
     * Get options in "key-value" format
     * @return array
     */
    public function toArray()
    {
        if (empty($this->mediaTypes)) {
            foreach ($this->getThemesInUse() as $theme) {
                $config = $this->viewConfig->getViewConfig([
                    'area' => Area::AREA_FRONTEND,
                    'themeModel' => $theme,
                ]);
                $images = $config->getMediaEntities(
                    'Magento_Catalog',
                    ImageHelper::MEDIA_TYPE_CONFIG_NODE
                );
                foreach ($images as $imageId => $imageData) {
                    if (strpos($imageId, 'unbxd') !== false) {
                        // don't process unbxd default images, declared in etc/view.xml
                        continue;
                    }
                    $height = isset($imageData['height']) ? $imageData['height'] : null;
                    $width = isset($imageData['width']) ? $imageData['width'] : null;
                    $value = $imageId;
                    $label = sprintf('%s (size not specified)', $imageId);
                    if ($height && $width) {
                        $value = sprintf('%s|%sx%s', $imageId, $height, $width);
                        $label = sprintf('%s (%sx%s)', $imageId, $height, $width);
                    }
                    $this->mediaTypes[$value] = $label;
                }
            }
            ksort($this->mediaTypes);
            array_unshift($this->mediaTypes, __('-- Please Select --'));
        }
        return $this->mediaTypes;
    }

    /**
     * Return array of options as value-label pairs
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        $optionArray = [];
        $arr = $this->toArray();
        foreach ($arr as $value => $label) {
            $optionArray[] = [
                'value' => $value,
                'label' => $label
            ];
        }
        return $optionArray;
    }
}