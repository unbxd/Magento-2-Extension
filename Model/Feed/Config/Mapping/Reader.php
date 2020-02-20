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

use Magento\Framework\Config\Reader\Filesystem;

/**
 * Class Reader
 * @package Unbxd\ProductFeed\Model\Feed\Config\Mapping
 */
class Reader extends Filesystem
{
    /**
     * List of identifier attributes for merging
     *
     * @var array
     */
    protected $_idAttributes = [
        '/config/product/fields/field' => 'name'
    ];
}
