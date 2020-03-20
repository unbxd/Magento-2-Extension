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
use Magento\Framework\Encryption\EncryptorInterface;

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
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * UpgradeData constructor.
     * @param StoreManagerInterface $storeManager
     * @param ConfigInterface $config
     * @param FeedHelper $feedHelper
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ConfigInterface $config,
        FeedHelper $feedHelper,
        EncryptorInterface $encryptor
    ) {
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->feedHelper = $feedHelper;
        $this->encryptor = $encryptor;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $this->updateConfigFields($setup);

        if (version_compare($context->getVersion(), '1.0.38', '<')) {
            $this->decryptSiteKeys($setup);
        }

        $setup->endSetup();
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @return $this
     */
    private function updateConfigFields(ModuleDataSetupInterface $setup)
    {
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

        return $this;
    }

    /**
     * Decrypt site key value for each store if it was stored in DB as encrypted value
     *
     * @param ModuleDataSetupInterface $setup
     * @return $this
     */
    private function decryptSiteKeys(ModuleDataSetupInterface $setup)
    {
        $select = $setup->getConnection()->select()->from(
            $setup->getTable('core_config_data'),
            ['*']
        )->where(
            'path LIKE ?',
            '%unbxd_setup/general/site_key%'
        );

        $affectedFields = $setup->getConnection()->fetchAll($select);
        foreach ($affectedFields as $rowData) {
            $scope = isset($rowData['scope']) ? $rowData['scope'] : null;
            $scopeId = isset($rowData['scope_id']) ? $rowData['scope_id'] : 0;
            $path = isset($rowData['path']) ? $rowData['path'] : null;
            $value = isset($rowData['value']) ? $rowData['value'] : null;
            if (!$scope || !$path || !$value) {
                continue;
            }

            // decrypt site key value if it was stored in DB as encrypted value
            $decryptedValue = $this->encryptor->decrypt($value);
            if ($decryptedValue) {
                $this->feedHelper->saveConfig($path, $decryptedValue, $scope, $scopeId);
            }
        }

        return $this;
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