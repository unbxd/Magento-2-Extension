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
namespace Unbxd\ProductFeed\Model\Indexer\Product\Full\Action;

use Unbxd\ProductFeed\Model\ResourceModel\Indexer\Product\Full\Action\Full as ResourceModel;
use Unbxd\ProductFeed\Model\Indexer\Product\Full\DataSourceProvider;
use Unbxd\ProductFeed\Logger\LoggerInterface;
use Unbxd\ProductFeed\Helper\Data as HelperData;
use Unbxd\ProductFeed\Model\Feed\Config as FeedConfig;

/**
 * Unbxd product feed full indexer.
 *
 * Class Full
 * @package Unbxd\ProductFeed\Model\Indexer\Product\Full\Action
 */
class Full
{
    /**
     * @var ResourceModel
     */
    private $resourceModel;

    /**
     * @var DataSourceProvider
     */
    private $dataSourceProvider;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var HelperData
     */
    private $helperData;

    /**
     * @var integer
     */
    private $batchRowsCount;

    /**
     * Full constructor.
     * @param ResourceModel $resourceModel
     * @param DataSourceProvider $dataSourceProvider
     * @param LoggerInterface $logger
     * @param HelperData $helperData
     * @param $batchRowsCount
     */
    public function __construct(
        ResourceModel $resourceModel,
        DataSourceProvider $dataSourceProvider,
        LoggerInterface $logger,
        HelperData $helperData,
        $batchRowsCount
    ) {
        $this->resourceModel = $resourceModel;
        $this->dataSourceProvider = $dataSourceProvider;
        $this->logger = $logger;
        $this->helperData = $helperData;
        $this->batchRowsCount = $batchRowsCount;
    }

    /**
     * Load a bulk of product data.
     *
     * @param $storeId
     * @param array $productIds
     * @param int $fromId
     * @param null $fromUpdatedDate
     * @param bool $useFilters
     * @param null $limit
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getProducts(
        $storeId,
        $productIds = [],
        $fromId = 0,
        $fromUpdatedDate = null,
        $useFilters = true,
        $limit = null
    ) {
        return $this->resourceModel->getProducts($storeId, $productIds, $fromId, $fromUpdatedDate, $useFilters, $limit);
    }

    /**
     * Get data for a list of product in a store ID.
     * If the product list IDs is null, all products data will be loaded.
     *
     * @param $storeId
     * @param array $productIds
     * @param null $fromUpdatedDate
     * @return \Generator
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function initProductStoreIndex($storeId, $productIds = [], $fromUpdatedDate = null)
    {
        if (!empty($productIds)) {
            //ensure the parent products are fetched
            $parentProducts = $this->resourceModel->getParentProductForChilds($productIds);
            if (!empty($parentProducts)) {
                $productIds = array_unique(array_merge($productIds, $parentProducts));
            }
            // ensure to reindex also the child product IDs, if parent was passed.
            $relationsByParent = $this->resourceModel->getRelationsByParent($productIds);
            if (!empty($relationsByParent)) {
                $productIds = array_unique(array_merge($productIds, $relationsByParent));
            }
        }

		$productId = 0;
        do {
            $products = $this->getProducts($storeId, $productIds, $productId, $fromUpdatedDate);
            foreach ($products as $productData) {
                $productId = (int) $productData['entity_id'];
                // check if product related to parent product, if so - mark it (use for filtering index data in feed process)
                $parentId = $this->resourceModel->getRelatedParentProduct($productId);
                if ($parentId && ($parentId != $productId)) {
                    $productData[FeedConfig::PARENT_ID_KEY] = (int) $parentId;
                };
                $productData['has_options'] = (bool) $productData['has_options'];
                $productData['required_options'] = (bool) $productData['required_options'];
                $productData['created_at'] = (string) $this->helperData->formatDateTime($productData['created_at']);
                $productData['updated_at'] = (string) $this->helperData->formatDateTime($productData['updated_at']);
                yield $productId => $productData;
            }
        } while (!empty($products));
    }

    /**
     * @param $data
     * @param $size
     * @return \Generator
     */
    private function getBatchItems($data, $size)
    {
        $i = 0;
        $batch = [];
        foreach ($data as $key => $value) {
            $batch[$key] = $value;
            if (++$i == $size) {
                yield $batch;
                $i = 0;
                $batch = [];
            }
        }
		
        if (count($batch) > 0) {
            yield $batch;
        }
    }

    /**
     * Append product index data on the basis of which feed operation will be performed
     *
     * @param $storeId
     * @param $initIndexData
     * @return array|mixed
     */
    private function appendIndexData($storeId, $initIndexData)
    {
        $index = [];
        $fields = [];
        $batchSize = $this->batchRowsCount;
        foreach ($this->getBatchItems($initIndexData, $batchSize) as $batchIndex) {
			if (!empty($batchIndex)) {
				foreach ($this->dataSourceProvider->getList() as $dataSource) {
					/** Unbxd\ProductFeed\Model\Indexer\Product\Full\DataSourceProviderInterface $dataSource */
					$batchIndex = $dataSource->appendData($storeId, $batchIndex);
				}
            }
            if (isset($batchIndex["fields"])){
            $fields = array_merge($fields,$batchIndex["fields"]);
            unset($batchIndex["fields"]);
            }
			if (!empty($batchIndex)) {
				$index += $batchIndex;
			}
        }
		$index["fields"]=$fields;
        return $index;
    }

    /**
     * Reindex all products data and return reindex result
     *
     * @param $storeId
     * @param array $productIds
     * @param null $fromUpdatedDate
     * @return array|mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function rebuildProductStoreIndex($storeId, $productIds = [], $fromUpdatedDate = null)
    {
        $initIndexData = $this->initProductStoreIndex($storeId, $productIds, $fromUpdatedDate);
        $fullIndex = [];
        if (!empty($initIndexData)) {
			$fullIndex = $this->appendIndexData($storeId, $initIndexData);
        }

        // try to detect deleted product(s)
        if (!empty($productIds)) {
            foreach ($productIds as $id) {
                if (!$this->resourceModel->getProductSkuById($id)) {
                    $fullIndex[$id]['action'] = FeedConfig::OPERATION_TYPE_DELETE;
                }
            }
        }

        return $fullIndex;
    }
}