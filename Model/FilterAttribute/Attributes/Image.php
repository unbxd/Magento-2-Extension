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
namespace Unbxd\ProductFeed\Model\FilterAttribute\Attributes;

use Unbxd\ProductFeed\Model\FilterAttribute\FilterAttributeInterface;

/**
 * Class Image
 * @package Unbxd\ProductFeed\Model\FilterAttribute
 */
class Image implements FilterAttributeInterface
{
    /**
     * Constant for attribute code
     */
    const ATTRIBUTE_CODE = 'image';

    /**
     * {@inheritdoc}
     */
    public function getAttributeCode()
    {
        return self::ATTRIBUTE_CODE;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return __('Without Images');
    }
}
