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

use Magento\Framework\Config\SchemaLocatorInterface;
use Magento\Framework\Module\Dir;
use Magento\Framework\Module\Dir\Reader as ModuleReader;
use Unbxd\ProductFeed\Helper\Module as ModuleHelper;

/**
 * Class SchemaLocator
 * @package Unbxd\ProductFeed\Model\Feed\Config\Mapping
 */
class SchemaLocator implements SchemaLocatorInterface
{
    /**
     * Path to corresponding XSD file with validation rules for merged configs
     *
     * @var string
     */
    private $schema;

    /**
     * Path to corresponding XSD file with validation rules for individual configs
     *
     * @var string
     */
    private $schemaFile;

    /**
     * @var ModuleHelper
     */
    private $moduleHelper;

    /**
     * SchemaLocator constructor.
     * @param ModuleReader $moduleReader
     * @param ModuleHelper $moduleHelper
     */
    public function __construct(
        ModuleHelper $moduleHelper,
        ModuleReader $moduleReader
    ) {
        $this->moduleHelper = $moduleHelper;
        $dir = $moduleReader->getModuleDir(Dir::MODULE_ETC_DIR, $this->moduleHelper->getModuleName());
        $this->schema = $dir . '/mapping.xsd';
    }

    /**
     * {@inheritdoc}
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * {@inheritdoc}
     */
    public function getPerFileSchema()
    {
        return $this->schemaFile;
    }
}