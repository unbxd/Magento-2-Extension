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
namespace Unbxd\ProductFeed\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\SetupInterface;
use Magento\Framework\Setup\UninstallInterface;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Unbxd\ProductFeed\Logger\OptionsListConstants;

/**
 * Class Uninstall
 * @package Unbxd\ProductFeed\Setup
 */
class Uninstall implements UninstallInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var WriteInterface
     */
    private $varDir;

    /**
     * Uninstall constructor.
     * @param ConfigInterface $config
     * @param Filesystem $filesystem
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        ConfigInterface $config,
        Filesystem $filesystem
    ) {
        $this->config = $config;
        $this->varDir = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        $this->deleteConfigFields($installer);
        $this->dropTables($installer);
        $this->dropColumns($installer);
        $this->dropFiles();

        $installer->endSetup();
    }


    /**
     * Remove related configuration fields
     *
     * @param SetupInterface $installer
     * @return void
     */
    private function deleteConfigFields($installer)
    {
        $select = $installer->getConnection()->select()->from(
            $installer->getTable('core_config_data'),
            ['scope', 'scope_id', 'path']
        )->where(
            'path LIKE ?',
            '%unbxd%'
        );

        $configRecords = $installer->getConnection()->fetchAll($select);
        foreach ($configRecords as $key => $data) {
            // for compatibility with versions ~2.1, in which parameters 'scope' and 'scope_id' are required
            $scope = isset($data['scope']) ? $data['scope'] : false;
            $scopeId = isset($data['scope_id']) ? $data['scope_id'] : false;
            $path = isset($data['path']) ? $data['path'] : false;
            if ($scope && $scopeId && $path) {
                $this->config->deleteConfig($path, $scope, $scopeId);
            }
        }
    }

    /**
     * Drop related tables
     *
     * @param SetupInterface $installer
     * @return void
     */
    private function dropTables($installer)
    {
        $tables = [
            $installer->getTable('unbxd_productfeed_indexing_queue'),
            $installer->getTable('unbxd_productfeed_feed_view')
        ];

        // just in case disable checks foreign key before drop tables action
        $installer->getConnection()->query('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($tables as $table) {
            if ($installer->tableExists($table)) {
                $installer->getConnection()->dropTable($table);
            }
        }
        // enable checks foreign key after drop tables action
        $installer->getConnection()->query('SET FOREIGN_KEY_CHECKS = 1');
    }

    /**
     * Drop related columns
     *
     * @param SetupInterface $installer
     * @return void
     */
    private function dropColumns($installer)
    {
        $relatedData = [
            $installer->getTable('catalog_eav_attribute') => 'include_in_unbxd_product_feed',
            $installer->getTable('catalog_eav_attribute') => 'unbxd_field_type',
            $installer->getTable('catalog_eav_attribute') =>  'include_in_unbxd_product_feed',
            $installer->getTable('catalog_eav_attribute') =>  'use_value_id',
            $installer->getTable('catalog_eav_attribute') =>  'export_swatch_image',
            $installer->getTable('catalog_eav_attribute') =>  'consider_attribute_onlyat_parent'
        ];
        foreach ($relatedData as $tableName => $columnName) {
            if ($installer->getConnection()->isTableExists($tableName)) {
                if ($installer->getConnection()->tableColumnExists($tableName, $columnName)) {
                    $installer->getConnection()->dropColumn($tableName, $columnName);
                }
            }
        }
    }

    /**
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function dropFiles()
    {
        // delete feed files if any
        if ($this->varDir->isExist(OptionsListConstants::LOGGER_SUB_DIR)) {
            $this->varDir->delete(OptionsListConstants::LOGGER_SUB_DIR);
        }
        // delete log files if any
        $logPath = sprintf('log/%s', OptionsListConstants::LOGGER_SUB_DIR);
        if ($this->varDir->isExist($logPath)) {
            $this->varDir->delete($logPath);
        }
    }
}