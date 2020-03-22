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
namespace Unbxd\ProductFeed\Block\Adminhtml\ViewDetails\Tab\Products\Grid\Column;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Store\Model\App\Emulation as AppEmulation;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\DataObject;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\AreaList;

/**
 * Class Renderer
 * @package Unbxd\ProductFeed\Block\Adminhtml\ViewDetails\Tab\Products\Grid\Column
 */
abstract class Renderer extends AbstractRenderer
{
    /**
     * @var AreaList
     */
    protected $areaList;

    /**
     * @var AppEmulation
     */
    protected $appEmulation;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Magento\Catalog\Helper\Image
     */
    protected $imageHelper;

    /**
     * @var \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable
     */
    protected $catalogProductTypeConfigurable;

    /**
     * Store manager
     *
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @var \Magento\Directory\Model\Currency\DefaultLocator
     */
    protected $currencyLocator;

    /**
     * @var \Magento\Directory\Model\Currency
     */
    protected $defaultBaseCurrency;

    /**
     * @var \Magento\Framework\Locale\CurrencyInterface
     */
    protected $localeCurrency;

    /**
     * Renderer constructor.
     * @param \Magento\Backend\Block\Context $context
     * @param AreaList $areaList
     * @param AppEmulation $appEmulation
     * @param ProductRepositoryInterface $productRepository
     * @param \Magento\Catalog\Helper\Image $imageHelper
     * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $catalogProductTypeConfigurable
     * @param StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Directory\Model\Currency\DefaultLocator $currencyLocator
     * @param \Magento\Directory\Model\Currency $defaultBaseCurrency
     * @param \Magento\Framework\Locale\CurrencyInterface $localeCurrency
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        AreaList $areaList,
        AppEmulation $appEmulation,
        ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Helper\Image $imageHelper,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $catalogProductTypeConfigurable,
        StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Directory\Model\Currency\DefaultLocator $currencyLocator,
        \Magento\Directory\Model\Currency $defaultBaseCurrency,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        array $data = []
    ){
        parent::__construct($context, $data);
        $this->areaList = $areaList;
        $this->appEmulation = $appEmulation;
        $this->productRepository = $productRepository;
        $this->urlBuilder = $context->getUrlBuilder();
        $this->imageHelper = $imageHelper;
        $this->catalogProductTypeConfigurable = $catalogProductTypeConfigurable;
        $this->storeManager = $storeManager;
        $this->productFactory = $productFactory;
        $this->currencyLocator = $currencyLocator;
        $this->defaultBaseCurrency = $defaultBaseCurrency;
        $this->localeCurrency = $localeCurrency;
    }

    /**
     * @param $id
     * @return \Magento\Catalog\Api\Data\ProductInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductById($id)
    {
        return $this->productRepository->getById($id, false, $this->getStore()->getId());
    }

    /**
     * Retrieve parent ids array by required child
     *
     * @param $childId
     * @return string[]
     */
    public function getParentIdsByChild($childId)
    {
        return $this->catalogProductTypeConfigurable->getParentIdsByChild($childId);
    }

    /**
     * @param $parentId
     * @return array
     */
    public function getChildrenIds($parentId)
    {
        return $this->catalogProductTypeConfigurable->getChildrenIds($parentId);
    }

    /**
     * @param null $storeId
     * @return \Magento\Store\Api\Data\StoreInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStore($storeId = null)
    {
        return $this->storeManager->getStore($storeId);
    }
}