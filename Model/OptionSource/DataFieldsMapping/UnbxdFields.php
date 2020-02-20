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
namespace Unbxd\ProductFeed\Model\OptionSource\DataFieldsMapping;

use Magento\Framework\Option\ArrayInterface;
use Unbxd\ProductFeed\Model\Feed\Config as FeedConfig;
use Magento\Framework\Config\DataInterface as ConfigDataInterface;

/**
 * Class UnbxdFields
 * @package Unbxd\ProductFeed\Model\OptionSource\DataFieldsMapping
 */
class UnbxdFields implements ArrayInterface
{
    /**
     * @var FeedConfig
     */
    private $feedConfig;

    /**
     * UnbxdFields constructor.
     * @param FeedConfig $feedConfig
     */
    public function __construct(
        FeedConfig $feedConfig
    ) {
        $this->feedConfig = $feedConfig;
    }

    /**
     * @return array
     */
    public function getDefaultOptions()
    {
        $uniqueId = FeedConfig::SPECIFIC_FIELD_KEY_UNIQUE_ID;
        $title = FeedConfig::SPECIFIC_FIELD_KEY_TITLE;
        $imageUrl = FeedConfig::SPECIFIC_FIELD_KEY_IMAGE_URL;
        $productUrl = FeedConfig::SPECIFIC_FIELD_KEY_PRODUCT_URL;
        $availability = FeedConfig::SPECIFIC_FIELD_KEY_AVAILABILITY;
        $category = FeedConfig::SPECIFIC_FIELD_KEY_CATEGORY_PATH_ID;

        $result = [
            $uniqueId => $this->convertToLabel($uniqueId),
            $title => $this->convertToLabel($title),
            $imageUrl => $this->convertToLabel($imageUrl),
            $productUrl => $this->convertToLabel($productUrl),
            $availability => $this->convertToLabel($availability),
            $category => $this->convertToLabel($category)
        ];

        asort($result);

        return $result;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $defaultDataFieldsMapping = $this->feedConfig->getDefaultDataFieldsMappingStorage();
        if (empty($defaultDataFieldsMapping)) {
            return $this->getDefaultOptions();
        }

        $result = [];
        // retrieve only Unbxd fields
        $fields = array_values($defaultDataFieldsMapping);
        foreach ($fields as $field) {
            $label = $this->convertToLabel($field);
            $result[$field] = sprintf('%s (%s)', $label, $field);
        }

        asort($result);

        return $result;
    }

    /**
     * @param $value
     * @return \Magento\Framework\Phrase
     */
    private function convertToLabel($value)
    {
        return __(ucwords(str_replace('_', ' ', $value)));
    }
}