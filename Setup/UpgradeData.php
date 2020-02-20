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
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Unbxd\ProductFeed\Helper\Feed as FeedHelper;

/**
 * Class UpgradeData
 * @package Unbxd\ProductFeed\Setup
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var FeedHelper
     */
    private $feedHelper;

    /**
     * UpgradeData constructor.
     * @param StoreManagerInterface $storeManager
     * @param ConfigInterface $config
     * @param FeedHelper $feedHelper
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ConfigInterface $config,
        FeedHelper $feedHelper
    ) {
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->feedHelper = $feedHelper;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $select = $setup->getConnection()->select()->from(
            $setup->getTable('core_config_data'),
            ['path', 'value']
        )->where(
            'path LIKE ?',
            '%unbxd_catalog/feed/%'
        );

        $alreadyInserted = $setup->getConnection()->fetchPairs($select);

        foreach ($this->feedHelper->getDefaultConfigFields() as $path => $value) {
            if (isset($alreadyInserted[$path])) {
                continue;
            }

            $this->feedHelper->saveConfig($path, $value);
        }

        $setup->endSetup();
    }

    /**
     * @return array
     */
    private function getWebsiteIds()
    {
        /** @var \Magento\Store\Api\Data\WebsiteInterface[] $websites */
        $websites = $this->storeManager->getWebsites();

        $websiteIds = [];
        foreach ($websites as $website) {
            array_push($websiteIds, $website->getId());
        }

        return $websiteIds;
    }

    /**
     * @return \Magento\Store\Api\Data\StoreInterface[]
     */
    private function getStores()
    {
        return $this->storeManager->getStores();
    }
}