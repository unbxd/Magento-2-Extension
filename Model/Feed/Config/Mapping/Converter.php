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
namespace Unbxd\ProductFeed\Model\Feed\Config\Mapping;

use Magento\Framework\Config\ConverterInterface;

/**
 * Class Converter
 * @package Unbxd\ProductFeed\Model\Feed\Config\Mapping
 */
class Converter implements ConverterInterface
{
    /**
     * Convert dom node tree to array
     *
     * @param \DOMDocument $source
     * @return array
     */
    public function convert($source)
    {
        $result = ['fields' => []];

        $fieldTypes = $source->getElementsByTagName('field');
        foreach ($fieldTypes as $fieldType) {
            /** @var \DOMNode $fieldType */
            $fieldName = $fieldType->attributes->getNamedItem('name')->nodeValue;
            $fieldType = $fieldType->attributes->getNamedItem('type')->nodeValue;
            $result['fields'][$fieldName] = $fieldType;
        }

        return $result;
    }
}
