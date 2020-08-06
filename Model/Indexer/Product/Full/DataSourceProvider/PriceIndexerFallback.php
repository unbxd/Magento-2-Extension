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
namespace Unbxd\ProductFeed\Model\Indexer\Product\Full\DataSourceProvider;

use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Indexer\Product\Price as PriceIndexer;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Mview\View\ChangelogTableNotExistsException;
use Unbxd\ProductFeed\Helper\AttributeHelper;
use Magento\Framework\App\ObjectManager;
use Unbxd\ProductFeed\Logger\LoggerInterface;
use Unbxd\ProductFeed\Model\Indexer\Product\Full\DataSourceProviderInterface;
use Magento\ConfigurableProduct\Pricing\Price\ConfigurableOptionsProviderInterface;

/**
 * Data source used to append prices data to product during indexing.
 *
 * Class Price
 * @package Unbxd\ProductFeed\Model\Indexer\Product\Full\DataSourceProvider
 */
class PriceIndexerFallback implements DataSourceProviderInterface
{
    /**
     * Related data source code
     */
    const DATA_SOURCE_CODE = 'pricefallback';

    /**
     * @var AttributeHelper
     */
    private $attributeHelper;

    private $productCollection;

    /** @var IndexerRegistry */
    private $indexerRegistry;

    /** @var PriceIndexer */
    private $priceIndexer;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    private $logger;

    private $configurableOptionsProvider;
    /**
     * Price constructor.
     * @param AttributeHelper $attributeHelper
     * @param array $priceReaderPool
     */
    public function __construct(
        AttributeHelper $attributeHelper,
        ProductCollectionFactory $productCollection,
        IndexerRegistry $indexerRegistry,
        PriceIndexer $indexer,
        ProductRepositoryInterface $productRepository,
        LoggerInterface $logger
    ) {
        $this->attributeHelper = $attributeHelper;
        $this->productCollection = $productCollection;
        $this->indexerRegistry = $indexerRegistry;
        $this->priceIndexer = $indexerRegistry->get('catalog_product_price');
        $this->productRepository = $productRepository;
        $this->logger = $logger->create("feed");
    }

    /**
     * {@inheritdoc}
     */
    public function getDataSourceCode()
    {
        return self::DATA_SOURCE_CODE;
    }

    /**
     * {@inheritdoc}
     */
    public function appendData($storeId, array $indexData)
    {
        if ($this->priceIndexer->isScheduled()) {
            try {
                $view = $this->priceIndexer->getView();
                $fromVersion = $view->getState()->getVersionId();
                $toVersion = $view->getChangelog()->getVersion();
                $pendingProductIds = $view->getChangelog()->getList(($fromVersion ? $fromVersion : 0), $toVersion);
            } catch (\ChangelogTableNotExistsException $ce) {
                $this->logger->debug("No changelog table available to evaluvate pending product id " . $ce->__toString());
            } catch (\Exception $e) {
                $this->logger->error("Unhandled error while processing :: " . $e->__toString());
            }
            if (!empty($pendingProductIds)) {
                $productIds = array_keys($indexData);
                array_splice($productIds, sizeof($productIds) - 1);
                $recheckProductsIds = array_intersect($productIds, $pendingProductIds);
                $this->appendParentProductForRecheck($recheckProductsIds, $indexData);
                $recheckProductsIds =array_unique($recheckProductsIds);
                $recheckProductString = print_r($recheckProductsIds, true);
                $this->logger->debug("The following products will be evaulavted for price changes ::" . $recheckProductString);
                foreach ($recheckProductsIds as $productId) {
                    $product = $this->productRepository->getById($productId, false, $storeId);
                    try {
                        if ($product->getTypeId() == 'configurable') {
                            $optionsPrice=$this->getCustomOptionsMinPrice($product);
                            $indexData[$productId]["price"] = $this->getMinPriceForConfigurableProduct($product)->getValue()+$optionsPrice;
                            if ($product->getPriceInfo()->getPrice('regular_price')->getMaxRegularAmount()) {
                                $indexData[$productId]["original_price"] = $product->getPriceInfo()->getPrice('regular_price')->getMaxRegularAmount()->getValue()+$optionsPrice;
                            }
                        } else if ($product->getTypeId() == 'grouped') {
                            $indexData[$productId]["price"] = $product->getPriceInfo()->getPrice('final_price')->getMinProduct()->getPriceInfo()->getPrice('final_price')->getValue();
                        } else if ($product->getTypeId() == 'bundle') {
                            $indexData[$productId]["price"] = $product->getPriceInfo()->getPrice('regular_price')->getMinimalPrice()->getValue();
                            if ($product->getPriceInfo()->getPrice('regular_price')->getMaximalPrice()->getValue()) {
                                $indexData[$productId]["original_price"] = $product->getPriceInfo()->getPrice('regular_price')->getMaximalPrice()->getValue();
                            }
                        } else {
                            $optionsPrice=$this->getCustomOptionsMinPrice($product);
                            if ($product->getFinalPrice() && $product->getFinalPrice() < $product->getPrice()) {
                                $indexData[$productId]["price"] = $product->getFinalPrice()+$optionsPrice;
                                $indexData[$productId]["original_price"] = $product->getPrice()+$optionsPrice;
                            } else {
                                $indexData[$productId]["price"] = $product->getPrice()+$optionsPrice;
                            }
                        }
                    } catch (\Exception $e) {
                        $this->logger->error("Error while processing price for product " . $productId . "with error - " . $e->__toString());
                    } catch (\Error $er) {
                        $this->logger->error("Error while processing price for product " . $productId . "with error - " . $er->__toString());
                    }
                }
                if (!empty($recheckProductsIds) && !array_key_exists("fields",$indexData)) {
                    $this->attributeHelper->appendSpecificIndexedFields($indexData, array('price', 'original_price'));
                }
            }
        }
        return $indexData;
    }

    private function getCustomOptionsMinPrice($product)
    {
        try {
            $customPrice = $product->getPriceInfo()->getPrice('custom_option_price');
            $optionsamount=0;
            if ($customPrice) {
                
                foreach($customPrice->getValue() as $optionsPrice){
                    $optionsamount += $optionsPrice["min"];
                };
            }
            return $optionsamount;
        } catch (\Exception $e) {
            $this->logger->error("Error while fetching custom_option_price for product " . $product->getId() . $e->getMessage());
        }
    }
    private function appendParentProductForRecheck(&$recheckProductsIds, $indexData)
    {
        foreach ($recheckProductsIds as $productId) {
            if (array_key_exists($productId, $indexData) && array_key_exists("parent_id", $indexData[$productId])) {
                $recheckProductsIds[] = $indexData[$productId]["parent_id"];
            }
        }
    }

    private function getMinPriceForConfigurableProduct($parentProduct)
    {
        $minAmount = null;
        foreach ($this->getConfigurableOptionsProvider()->getProducts($parentProduct) as $product) {
            $childPriceAmount = $product->getPriceInfo()->getPrice("regular_price")->getAmount();
            if (!$minAmount || ($childPriceAmount->getValue() < $minAmount->getValue())) {
                $minAmount = $childPriceAmount;
            }
        }
        return $minAmount;
    }

    /**
     * @return \Magento\ConfigurableProduct\Pricing\Price\ConfigurableOptionsProviderInterface
     * @deprecated 100.1.1
     */
    private function getConfigurableOptionsProvider()
    {
        if (null === $this->configurableOptionsProvider) {
            $this->configurableOptionsProvider = ObjectManager::getInstance()
                ->get(ConfigurableOptionsProviderInterface::class);
        }
        return $this->configurableOptionsProvider;
    }

}