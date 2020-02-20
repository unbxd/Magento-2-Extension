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
namespace Unbxd\ProductFeed\Model\Indexer\Product\Full;

use Unbxd\ProductFeed\Model\Indexer\Product\Full\DataSourceProviderInterface;

/**
 * Class DataSourceProvider
 * @package Unbxd\ProductFeed\Model\Indexer\Product\Full
 */
class DataSourceProvider
{
    const DATA_SOURCES_DEFAULT_TYPE = 'product';

    /**
     * @var string
     */
    private $typeName;

    /**
     * @var DataSourceProviderInterface[]
     */
    private $dataSources = [];

    /**
     * DataSourceProvider constructor.
     * @param string $typeName
     * @param array $dataSources
     */
    public function __construct(
        $typeName = self::DATA_SOURCES_DEFAULT_TYPE,
        $dataSources = []
    ) {
        $this->typeName = $typeName;
        $this->dataSources = $dataSources;
    }

    /**
     * Retrieve data sources type name
     *
     * @return mixed
     */
    public function getTypeName()
    {
        return $this->typeName;
    }

    /**
     * Retrieve data sources list.
     *
     * @return DataSourceProviderInterface[]
     */
    public function getList()
    {
        return $this->dataSources;
    }

    /**
     * Retrieve a special data source by code.
     *
     * @param $dataSourceCode
     * @return DataSourceProviderInterface|null
     */
    public function getDataSource($dataSourceCode)
    {
        return isset($this->dataSources[$dataSourceCode]) ? $this->dataSources[$dataSourceCode] : null;
    }
}