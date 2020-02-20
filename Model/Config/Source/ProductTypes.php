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
use Magento\Catalog\Model\Product\TypeFactory;

/**
 * Class ProductTypes
 * @package Unbxd\ProductFeed\Model\Config\Source
 */
class ProductTypes implements ArrayInterface
{
    /**
     * Constant for all supported product types
     */
    const ALL_KEY = 'all';

    /**
     * Product type model
     *
     * @var \Magento\Catalog\Model\Product\TypeFactory
     */
    protected $typeFactory;

    /**
     * Flag whether to add product all types or no
     *
     * @var bool
     */
    protected $addAllTypes = true;

    /**
     * Supported product types
     *
     * @var array
     */
    private $supportedTypes = [
        \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE,
        \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE,
        \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL,
        \Magento\Bundle\Model\Product\Type::TYPE_CODE,
        \Magento\Downloadable\Model\Product\Type::TYPE_DOWNLOADABLE
    ];

    /**
     * ProductTypes constructor.
     * @param TypeFactory $typeFactory
     */
    public function __construct(
        TypeFactory $typeFactory
    ) {
        $this->typeFactory = $typeFactory;
    }

    /**
     * Get all supported product types
     *
     * @return array
     */
    public function getAllSupportedProductTypes()
    {
        return $this->supportedTypes;
    }

    /**
     * Retrieve product types
     *
     * @return array
     */
    protected function getProductTypes()
    {
        /** @var \Magento\Catalog\Model\Product\Type $type */
        $type = $this->typeFactory->create();
        $types = $type->getTypes();
        uasort(
            $types,
            function ($elementOne, $elementTwo) {
                return ($elementOne['sort_order'] < $elementTwo['sort_order']) ? -1 : 1;
            }
        );

        $result = [];
        foreach ($types as $typeId => $type) {
            $result[$typeId] = isset($type['label'])
                ? $type['label']
                : sprintf('%s Product', ucfirst($typeId));
        }

        if ($this->addAllTypes && !array_key_exists(self::ALL_KEY, $result)) {
            $result = [self::ALL_KEY => __('All AVAILABLE TYPES')] + $result;
        }

        return $result;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return $this->getProductTypes();
    }

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
}
