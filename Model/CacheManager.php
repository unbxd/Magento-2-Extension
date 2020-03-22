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

use Magento\Framework\Event\ManagerInterface as EventManagerInterface;

/**
 * Class CacheManager
 * @package Unbxd\ProductFeed\Model
 */
class CacheManager
{
    /**
     * @var EventManagerInterface
     */
    protected $_eventManager;

    /**
     * @var \Magento\Framework\App\Cache\TypeListInterface
     */
    protected $_cacheTypeList;

    /**
     * @var \Magento\Framework\App\Cache\Frontend\Pool
     */
    protected $_cacheFrontendPool;

    /**
     * @var array
     */
    private $cacheTypes = [];

    /**
     * CacheManager constructor.
     * @param EventManagerInterface $eventManager
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool
     * @param array $cacheTypes
     */
    public function __construct(
        EventManagerInterface $eventManager,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool,
        array $cacheTypes = []
    ) {
        $this->_eventManager = $eventManager;
        $this->_cacheTypeList = $cacheTypeList;
        $this->_cacheFrontendPool = $cacheFrontendPool;
        $this->cacheTypes = $cacheTypes;
    }

    /**
     * Flush cache by specific types
     *
     * @param array $types
     * @return $this
     */
    public function flushByTypes(array $types = [])
    {
        $isNotValid = $this->validateTypes($types);
        if ($isNotValid) {
            return $this;
        }

        foreach ($types as $type) {
            $this->_cacheTypeList->cleanType($type);
        }

        return $this;
    }

    /**
     * Check whether specified cache types exist
     *
     * @param array $types
     * @return bool
     */
    private function validateTypes(array $types = [])
    {
        if (empty($types)) {
            $types = $this->cacheTypes;
        }

        $allTypes = array_keys($this->_cacheTypeList->getTypes());
        $invalidTypes = array_diff($types, $allTypes);

        return (bool) (count($invalidTypes) > 0);
    }

    /**
     * Flush cache storage
     *
     * @return $this
     */
    public function flushAll()
    {
        $this->_eventManager->dispatch('adminhtml_cache_flush_all');
        /** @var \Magento\Framework\Cache\FrontendInterface $cacheFrontend */
        foreach ($this->_cacheFrontendPool as $cacheFrontend) {
            $cacheFrontend->getBackend()->clean();
        }

        return $this;
    }
}