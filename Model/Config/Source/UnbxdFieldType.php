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

use Magento\Framework\Option\ArrayInterface;

/**
 * Class CronType
 * @package Unbxd\ProductFeed\Model\Config\Source
 */
class UnbxdFieldType implements ArrayInterface
{
    const TEXT = 'text';
    const LONGTEXT = 'longText';
    const DECIMAL = 'decimal';
    const NUMERIC = 'number';
    const LINK = 'link';
    const DATE = 'date';
    const YESNO = 'bool';
    const SKU = 'sku';
    const PATH = 'path';
    const FACET = 'facet';
    const NSKU = 'nsku';
    

    /**
     * Return array of options as value-label pairs
     *
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

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $options = [
            null => __('Choose an override datatype'),
            self::TEXT => __('Text'),
            self::LONGTEXT => __('Long Text'),
            self::DECIMAL => __('Decimal'),
            self::NUMERIC => __('Numeric'),
            self::SKU => __('SKU'),
            self::NSKU => __('NSKU'),
            self::YESNO => __('Boolean'),
            self::PATH => __('Path'),
            self::FACET => __('Facet'),
            self::LINK => __('Link'),
            self::DATE => __('date')
            
        ];

        return $options;
    }
}
