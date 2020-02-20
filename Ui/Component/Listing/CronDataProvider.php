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
namespace Unbxd\ProductFeed\Ui\Component\Listing;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Unbxd\ProductFeed\Model\ResourceModel\Cron\Collection;
use Unbxd\ProductFeed\Model\ResourceModel\Cron\CollectionFactory;

/**
 * Class CronDataProvider
 * @package Unbxd\ProductFeed\Ui\Component\Listing
 */
class CronDataProvider extends AbstractDataProvider
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * CronDataProvider constructor.
     * @param CollectionFactory $collectionFactory
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        $name,
        $primaryFieldName,
        $requestFieldName,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $meta,
            $data
        );
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @return \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
     */
    public function getCollection()
    {
        if (!$this->collection) {
            /** @var Collection collection */
            $this->collection = $this->collectionFactory->create()->filterCollectionByRelatedJobs();
        }

        return $this->collection;
    }
}