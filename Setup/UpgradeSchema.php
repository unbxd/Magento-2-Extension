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

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

/**
 * Class UpgradeSchema
 * @package Unbxd\ProductFeed\Setup
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        /**
         * Create table 'unbxd_productfeed_indexing_queue'
         */
        if (!$installer->tableExists('unbxd_productfeed_indexing_queue')) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('unbxd_productfeed_indexing_queue')
            )->addColumn(
                'queue_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'primary' => true, 'nullable' => false, 'unsigned' => true],
                'Queue Id'
            )->addColumn(
                'store_id',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true],
                'Store Id'
            )->addColumn(
                'created_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                'Creation Time'
            )->addColumn(
                'started_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => '0000-00-00 00:00:00'],
                'Started Time'
            )->addColumn(
                'finished_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => '0000-00-00 00:00:00'],
                'Finished Time'
            )->addColumn(
                'is_active',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => '0'],
                'Is Active'
            )->addColumn(
                'execution_time',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false],
                'Execution Time'
            )->addColumn(
                'affected_entities',
                Table::TYPE_TEXT,
                null,
                [],
                'Affected Entities'
            )->addColumn(
                'number_of_entities',
                Table::TYPE_INTEGER,
                null,
                ['default' => '0'],
                'Number Of Entities'
            )->addColumn(
                'action_type',
                Table::TYPE_TEXT,
                32,
                [],
                'Action Type'
            )->addColumn(
                'status',
                Table::TYPE_TEXT,
                32,
                [],
                'Status'
            )->addColumn(
                'additional_information',
                Table::TYPE_TEXT,
                null,
                [],
                'Additional Information'
            )->addColumn(
                'system_information',
                Table::TYPE_TEXT,
                null,
                [],
                'System Information'
            )->addIndex(
                $installer->getIdxName('unbxd_productfeed_indexing_queue', ['queue_id']),
                ['queue_id']
            )->addIndex(
                $installer->getIdxName('unbxd_productfeed_indexing_queue', ['status']),
                ['status']
            );

            $installer->getConnection()->createTable($table);
        };

        /**
         * Create table 'unbxd_productfeed_feed_view'
         */
        if (!$installer->tableExists('unbxd_productfeed_feed_view')) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('unbxd_productfeed_feed_view')
            )->addColumn(
                'feed_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true],
                'Feed Id'
            )->addColumn(
                'store_id',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true],
                'Store Id'
            )->addColumn(
                'created_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                'Creation Time'
            )->addColumn(
                'finished_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => '0000-00-00 00:00:00'],
                'Finished Time'
            )->addColumn(
                'is_active',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => '0'],
                'Is Active'
            )->addColumn(
                'execution_time',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false],
                'Execution Time'
            )->addColumn(
                'affected_entities',
                Table::TYPE_TEXT,
                null,
                [],
                'Affected Entities'
            )->addColumn(
                'number_of_entities',
                Table::TYPE_INTEGER,
                null,
                ['default' => '0'],
                'Number Of Entities'
            )->addColumn(
                'operation_types',
                Table::TYPE_TEXT,
                null,
                ['unsigned' => true],
                'Operation Types'
            )->addColumn(
                'status',
                Table::TYPE_TEXT,
                32,
                [],
                'Status'
            )->addColumn(
                'additional_information',
                Table::TYPE_TEXT,
                null,
                [],
                'Additional Information'
            )->addColumn(
                'system_information',
                Table::TYPE_TEXT,
                null,
                [],
                'System Information'
            )->addColumn(
                'upload_id',
                Table::TYPE_TEXT,
                null,
                [],
                'Upload ID'
            )->addIndex(
                $installer->getIdxName('unbxd_productfeed_feed_view', ['feed_id']),
                ['feed_id']
            )->addIndex(
                $installer->getIdxName('unbxd_productfeed_feed_view', ['status']),
                ['status']
            )->setComment(
                'Unbxd ProductFeed Synchronization View Table'
            );

            $installer->getConnection()->createTable($table);
        }

        if (version_compare($context->getVersion(), '1.0.20', '<')) {
            $columnName = 'number_of_attempts';
            $indexingQueueTable = $installer->getTable('unbxd_productfeed_indexing_queue');
            if (
                $installer->tableExists($indexingQueueTable)
                && !$installer->getConnection()->tableColumnExists($indexingQueueTable, $columnName)
            ) {
                $installer->getConnection()->addColumn(
                    $indexingQueueTable,
                    $columnName,
                    [
                        'type' => Table::TYPE_SMALLINT,
                        'default' => 0,
                        'comment' => 'The Number Of Attempts',
                    ]
                );
            }

            $feedViewTable = $installer->getTable('unbxd_productfeed_feed_view');
            if (
                $installer->tableExists($feedViewTable)
                && !$installer->getConnection()->tableColumnExists($feedViewTable, $columnName)
            ) {
                $installer->getConnection()->addColumn(
                    $feedViewTable,
                    $columnName,
                    [
                        'type' => Table::TYPE_SMALLINT,
                        'default' => 0,
                        'comment' => 'The Number Of Attempts',
                    ]
                );
            }
        }

        // create attribute parameter to include/exclude from product feed
        $catalogEavAttributeTable = $installer->getTable('catalog_eav_attribute');
        if (
            $installer->tableExists($catalogEavAttributeTable)
            && !$installer->getConnection()->tableColumnExists(
                $catalogEavAttributeTable, 'include_in_unbxd_product_feed'
            )
        ) {
            $installer->getConnection()
                ->addColumn(
                    $catalogEavAttributeTable,
                    'include_in_unbxd_product_feed',
                    [
                        'type' => Table::TYPE_SMALLINT,
                        'nullable' => false,
                        'unsigned' => true,
                        'length' => 1,
                        'default' => 1,
                        'comment' => 'Include In Unbxd Product Feed',
                    ]
                );
        }

        if (
            $installer->tableExists($catalogEavAttributeTable)
            && !$installer->getConnection()->tableColumnExists(
                $catalogEavAttributeTable, 'use_value_id'
            )
        ) {
            $installer->getConnection()
                ->addColumn(
                    $catalogEavAttributeTable,
                    'use_value_id',
                    [
                        'type' => Table::TYPE_SMALLINT,
                        'nullable' => true,
                        'unsigned' => true,
                        'length' => 1,
                        'default' => 0,
                        'comment' => 'Use value id instead of option value',
                    ]
                );
        }

        if (
            $installer->tableExists($catalogEavAttributeTable)
            && !$installer->getConnection()->tableColumnExists(
                $catalogEavAttributeTable, 'unbxd_field_type'
            )
        ) {
            $installer->getConnection()
                ->addColumn(
                    $catalogEavAttributeTable,
                    'unbxd_field_type',
                    [
                        'type' => Table::TYPE_TEXT,
                        'nullable' => true,
                        'length' => 255,
                        'comment' => 'Special field type classification',
                    ]
                );
        }

        if (
            $installer->tableExists($catalogEavAttributeTable)
            && !$installer->getConnection()->tableColumnExists(
                $catalogEavAttributeTable, 'unbxd_multiselect_override'
            )
        ) {
            $installer->getConnection()
                ->addColumn(
                    $catalogEavAttributeTable,
                    'unbxd_multiselect_override',
                    [
                        'type' => Table::TYPE_BOOLEAN,
                        'nullable' => true,
                        'comment' => 'When set to true will override the computed value',
                    ]
                );
        }

        if (
            $installer->tableExists($catalogEavAttributeTable)
            && !$installer->getConnection()->tableColumnExists(
                $catalogEavAttributeTable, 'consider_attribute_onlyat_parent'
            )
        ) {
            $installer->getConnection()
                ->addColumn(
                    $catalogEavAttributeTable,
                    'consider_attribute_onlyat_parent',
                    [
                        'type' => Table::TYPE_BOOLEAN,
                        'nullable' => true,
                        'comment' => 'Do not rollup child attribute values in composite product.',
                    ]
                );
        }

        // add fields which link re-index operation with synchronization process and vice versa
        if (version_compare($context->getVersion(), '1.0.40', '<')) {
            $indexingQueueColumnName = 'feed_view_id';
            $indexingQueueTable = $installer->getTable('unbxd_productfeed_indexing_queue');
            if (
                $installer->tableExists($indexingQueueTable)
                && !$installer->getConnection()->tableColumnExists($indexingQueueTable, $indexingQueueColumnName)
            ) {
                $installer->getConnection()->addColumn(
                    $indexingQueueTable,
                    $indexingQueueColumnName,
                    [
                        'type' => Table::TYPE_INTEGER,
                        'default' => null,
                        'comment' => 'Feed View ID',
                    ]
                );
            }

            $feedViewColumnName = 'reindex_job_id';
            $feedViewTable = $installer->getTable('unbxd_productfeed_feed_view');
            if (
                $installer->tableExists($feedViewTable)
                && !$installer->getConnection()->tableColumnExists($feedViewTable, $feedViewColumnName)
            ) {
                $installer->getConnection()->addColumn(
                    $feedViewTable,
                    $feedViewColumnName,
                    [
                        'type' => Table::TYPE_INTEGER,
                        'default' => null,
                        'comment' => 'Reindex Job ID',
                    ]
                );
            }
        }
        // change the definition for some date time fields
        if (version_compare($context->getVersion(), '1.0.41', '<')) {
            $affectedData = [
                $installer->getTable('unbxd_productfeed_indexing_queue') => [
                    'started_at' => [
                        'type' => Table::TYPE_TIMESTAMP,
                        'nullable' => true,
                        'unsigned' => true,
                        'default' => '',
                        'comment' => 'Started Time',
                    ],
                    'finished_at' => [
                        'type' => Table::TYPE_TIMESTAMP,
                        'nullable' => true,
                        'unsigned' => true,
                        'default' => '',
                        'comment' => 'Finished Time',
                    ],
                ],
                $installer->getTable('unbxd_productfeed_feed_view') => [
                    'finished_at' => [
                        'type' => Table::TYPE_TIMESTAMP,
                        'nullable' => true,
                        'unsigned' => true,
                        'default' => '',
                        'comment' => 'Finished Time',
                    ],
                ],
            ];

            foreach ($affectedData as $tableName => $columns) {
                if ($installer->tableExists($tableName)) {
                    foreach ($columns as $columnName => $definition) {
                        if (
                            $installer->getConnection()->tableColumnExists($tableName, $columnName)
                            && !empty($definition)
                        ) {
                            $installer->getConnection()->modifyColumn(
                                $tableName,
                                $columnName,
                                $definition
                            );
                        }
                    }
                }
            }
        }

        if (version_compare($context->getVersion(), '1.0.65', '<')) {
            if ($installer->getConnection()->tableColumnExists($installer->getTable('unbxd_productfeed_indexing_queue'), "queue_id")) {
                $installer->getConnection()->modifyColumn(
                    $installer->getTable('unbxd_productfeed_indexing_queue'),
                    "queue_id",
                    [
                        'type' => Table::TYPE_INTEGER,
                        'nullable' => false,
                        'unsigned' => true,
                        'comment' => 'Queue Id',
                        'identity' => true
                    ]
                );
            }
        }

        $installer->endSetup();
    }
}
