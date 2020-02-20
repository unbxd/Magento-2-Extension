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
namespace Unbxd\ProductFeed\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as AttributeResource;
use Magento\Eav\Model\Config as EavConfig;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Helper\Stock as StockHelper;
use Magento\Directory\Model\Currency as CurrencyHelper;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Unbxd\ProductFeed\Model\Config\Source\ProductTypes;

/**
 * Helper class to perform operations with product entity
 *
 * Class Product
 * @package Unbxd\ProductFeed\Helper
 */
class ProductHelper
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var Visibility
     */
    private $visibility;

    /**
     * @var Status
     */
    private $status;

    /**
     * @var Type
     */
    private $type;

    /**
     * @var Configurable
     */
    private $configurable;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var AttributeResource
     */
    private $attributeResource;

    /**
     * @var EavConfig
     */
    private $eavConfig;

    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @var StockHelper
     */
    private $stockHelper;

    /**
     * @var $storeManager
     */
    private $storeManager;

    /**
     * @var CurrencyHelper
     */
    private $currencyHelper;

    /**
     * @var EventManagerInterface
     */
    private $eventManagerInterface;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var AbstractType[]
     */
    private $compositeTypes;

    /**
     * Local cache for attribute codes
     *
     * @var array
     */
    private $productAttributesCodes = [];

    /**
     * @var ProductTypes
     */
    private $productTypes;

    /**
     * ProductHelper constructor.
     * @param ProductRepositoryInterface $productRepository
     * @param Visibility $visibility
     * @param Status $status
     * @param Type $type
     * @param Configurable $configurable
     * @param CollectionFactory $collectionFactory
     * @param AttributeResource $attributeResource
     * @param EavConfig $eavConfig
     * @param StockRegistryInterface $stockRegistry
     * @param StockHelper $stockHelper
     * @param StoreManagerInterface $storeManager
     * @param CurrencyHelper $currencyHelper
     * @param EventManagerInterface $eventManager
     * @param ObjectManagerInterface $objectManager
     * @param ProductTypes $productTypes
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        Visibility $visibility,
        Status $status,
        Type $type,
        Configurable $configurable,
        CollectionFactory $collectionFactory,
        AttributeResource $attributeResource,
        EavConfig $eavConfig,
        StockRegistryInterface $stockRegistry,
        StockHelper $stockHelper,
        StoreManagerInterface $storeManager,
        CurrencyHelper $currencyHelper,
        EventManagerInterface $eventManager,
        ObjectManagerInterface $objectManager,
        ProductTypes $productTypes
    ) {
        $this->productRepository = $productRepository;
        $this->visibility = $visibility;
        $this->status = $status;
        $this->type = $type;
        $this->configurable = $configurable;
        $this->collectionFactory = $collectionFactory;
        $this->attributeResource = $attributeResource;
        $this->eavConfig = $eavConfig;
        $this->stockRegistry = $stockRegistry;
        $this->stockHelper = $stockHelper;
        $this->storeManager = $storeManager;
        $this->currencyHelper = $currencyHelper;
        $this->eventManagerInterface = $eventManager;
        $this->objectManager = $objectManager;
        $this->productTypes = $productTypes;
    }

    /**
     * @param $id
     * @return \Magento\Catalog\Api\Data\ProductInterface|null
     */
    public function getProduct($id)
    {
        $product = null;
        try {
            /** @var \Magento\Catalog\Api\Data\ProductInterface $product */
            $product = $this->productRepository->getById($id);
        } catch (\Exception $e) {
            // log exception
            return $product;
        }

        return $product;
    }

    /**
     * Retrieve all ids for product collection
     *
     * @return array
     */
    public function getAllProductsIds()
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection */
        $productCollection = $this->collectionFactory->create();
        return $productCollection->getAllIds();
    }

    /**
     * Returns composite product type instances
     *
     * @return AbstractType[]|null
     */
    public function getCompositeTypes()
    {
        if ($this->compositeTypes === null) {
            $productEmulator = new DataObject();
            foreach ($this->type->getCompositeTypes() as $typeId) {
                $productEmulator->setTypeId($typeId);
                $this->compositeTypes[$typeId] = $this->type->factory($productEmulator);
            }
        }

        return $this->compositeTypes;
    }

    /**
     * @param $typeId
     * @return bool
     */
    public function isProductTypeSupported($typeId)
    {
        return array_key_exists($typeId, $this->productTypes->toArray());
    }

    /**
     * Returns all parent product IDs, e.g. when simple product is part of configurable
     *
     * @param array $productIds
     * @return array
     */
    public function getParentProductIds(array $productIds)
    {
        $parentIds = [];
        foreach ($this->getCompositeTypes() as $typeInstance) {
            $parentIds = array_merge($parentIds, $typeInstance->getParentIdsByChild($productIds));
        }

        return $parentIds;
    }

    /**
     * Returns all child product IDs, e.g. when product configurable or grouped
     *
     * @param int|array $parentId
     * @return array
     */
    public function getChildProductIds($parentId)
    {
        $childIds = [];
        foreach ($this->getCompositeTypes() as $typeInstance) {
            $childIds = array_merge($childIds, $typeInstance->getChildrenIds($parentId));
        }

        return $childIds;
    }

    /**
     * Returns all child product IDs for specific store
     *
     * @param int $parentId
     * @param $storeId
     * @return array
     */
    public function getChildProductIdsByStore($parentId, $storeId)
    {
        $childGroupData = $this->getChildProductIds($parentId);
        $childIds = array_key_exists($storeId, $childGroupData) ? array_values($childGroupData[$storeId]) : [];

        return $childIds;
    }

    /**
     * @param $value
     * @return mixed
     */
    public function getVisibilityTypeLabelByValue($value)
    {
        return $this->visibility->getOptionText($value);
    }
}