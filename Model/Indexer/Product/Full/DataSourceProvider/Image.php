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

use Unbxd\ProductFeed\Model\Indexer\Product\Full\DataSourceProviderInterface;
use Unbxd\ProductFeed\Model\ResourceModel\Indexer\Product\Full\DataSourceProvider\Image as ResourceModel;
use Magento\Catalog\Model\Product\Media\Config as MediaConfig;

/**
 * Data source used to append image data to product during indexing.
 *
 * Class Image
 * @deprecated
 * @package Unbxd\ProductFeed\Model\Indexer\Product\Full\DataSourceProvider
 */
class Image implements DataSourceProviderInterface
{
    /**
     * Related data source code
     */
    const DATA_SOURCE_CODE = 'image';

    /**
     * @var ResourceModel
     */
    private $resourceModel;

    /**
     * @var MediaConfig
     */
    protected $catalogProductMediaConfig;

    /**
     * Image constructor.
     * @param ResourceModel $resourceModel
     * @param MediaConfig $catalogProductMediaConfig
     */
    public function __construct(
        ResourceModel $resourceModel,
        MediaConfig $catalogProductMediaConfig
    ) {
        $this->resourceModel = $resourceModel;
        $this->catalogProductMediaConfig = $catalogProductMediaConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function getDataSourceCode()
    {
        return self::DATA_SOURCE_CODE;
    }

    /**
     * Append image data to the product index data
     *
     * {@inheritdoc}
     */
    public function appendData($storeId, array $indexData)
    {
        $imageData = $this->resourceModel->loadImagesData($storeId, array_keys($indexData));
        foreach ($imageData as $imageDataRow) {
            $productId = (int) $imageDataRow['product_id'];
            $filepath = (string) $imageDataRow['filepath'];
            $position = (int) $imageDataRow['position'];
            $isDisabled = (bool) $imageDataRow['disabled'];
            if (!$isDisabled) {
                $indexData[$productId]['images'][] = [
                    'filepath' => $filepath,
                    'position' => $position
                ];
            }
        }

        return $indexData;
    }
}