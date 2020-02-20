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
namespace Unbxd\ProductFeed\Model;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Cache\StateInterface;
use Unbxd\ProductFeed\Model\Serializer;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Cache\FrontendInterface as FrontendInterface;
use Magento\Framework\App\ProductMetadata;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Cache\Type\Config;

/**
 * Class CacheManager
 * @package Unbxd\ProductFeed\Model
 */
class CacheManager
{
    /**
     * @var integer
     */
    const DEFAULT_LIFETIME = 9600;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var FrontendInterface
     */
    protected $configCache;

    /**
     * @var TypeListInterface
     */
    private $cacheTypeList;

    /**
     * @var StateInterface
     */
    private $cacheState;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var array
     */
    private $localCache = [];

    /**
     * @var ProductMetadata
     */
    private $productMetadata;

    /**
     * @var ReinitableConfigInterface
     */
    private $reinitableConfig;

    /**
     * CacheManager constructor.
     * @param CacheInterface $cache
     * @param TypeListInterface $cacheTypeList
     * @param StateInterface $cacheState
     * @param \Unbxd\ProductFeed\Model\Serializer $serializer
     * @param ProductMetadata $productMetadata
     * @param ReinitableConfigInterface $reinitableConfig
     */
    public function __construct(
        CacheInterface $cache,
        TypeListInterface $cacheTypeList,
        StateInterface $cacheState,
        Serializer $serializer,
        ProductMetadata $productMetadata,
        ReinitableConfigInterface $reinitableConfig
    ) {
        $this->cache = $cache;
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheState = $cacheState;
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(Serializer::class);
        $this->productMetadata = $productMetadata;
        $this->reinitableConfig = $reinitableConfig;
    }

    /**
     * Save data into an cache.
     *
     * @param $cacheKey
     * @param $data
     * @param array $cacheTags
     * @param int $lifetime
     */
    public function save($cacheKey, $data, $cacheTags = [], $lifetime = self::DEFAULT_LIFETIME)
    {
        $this->localCache[$cacheKey] = $data;
        if (!is_string($data)) {
            $data = $this->serializer->serialize($data);
        }

        $this->cache->save($data, $cacheKey, $cacheTags, $lifetime);
    }

    /**
     * Load data from the cache
     *
     * @param $cacheKey
     * @return mixed
     */
    public function load($cacheKey)
    {
        if (!isset($this->localCache[$cacheKey])) {
            $data = $this->cache->load($cacheKey);
            if ($data) {
                $data = $this->serializer->unserialize($data);
            }
            $this->localCache[$cacheKey] = $data;
        }

        return $this->localCache[$cacheKey];
    }

    /**
     * Clean the cache by identifier and store
     *
     * @param $identifier
     * @param $storeId
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function clean($identifier, $storeId)
    {
        $cacheTags = $this->getCacheTags($identifier, $storeId);
        $this->localCache = [];
        $this->cache->clean($cacheTags);
    }

    /**
     * Get cache tag by identifier / store.
     *
     * @param $identifier
     * @param $storeId
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getCacheTags($identifier, $storeId)
    {
        return [$identifier . '_' . $storeId];
    }

    /**
     * Flush cache by type
     *
     * @param $cacheType
     */
    public function flushCacheByType($cacheType)
    {
        if ($this->validateTypes([$cacheType])) {
            if ($this->cacheState->isEnabled($cacheType)) {
                $this->cacheTypeList->cleanType($cacheType);
            }
        }
    }

    /**
     * Check whether specified cache types exist
     *
     * @param array $types
     * @return bool
     */
    private function validateTypes(array $types)
    {
        $allTypes = array_keys($this->cacheTypeList->getTypes());
        $invalidTypes = array_diff($types, $allTypes);
        if (count($invalidTypes) > 0) {
            return false;
        }

        return true;
    }

    /**
     * Clean config cache records
     */
    public function flushSystemConfigCache()
    {
        if (strpos($this->productMetadata->getVersion(), '2.2') !== false) {
            // in versions ~2.2 detect frontend issues after clear config cache
            // @TODO - implement
        } else {
            try {
                $this->reinitableConfig->reinit();
            } catch (\Exception $e) {
                $this->cacheTypeList->invalidate(Config::TYPE_IDENTIFIER);
            }
        }
    }
}