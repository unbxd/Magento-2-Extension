<?php

namespace Unbxd\ProductFeed\Model\Indexer\Product\Full\DataSourceProvider;

/**
 * @author      Jag S <jagadeesh@oceaniasolution.com>
 */

use Unbxd\ProductFeed\Model\Indexer\Product\Full\DataSourceProviderInterface;
use Unbxd\ProductFeed\Logger\LoggerInterface;
use Unbxd\ProductFeed\Model\Feed\Config;
use Exception;

class PartialUpdateIdentifier implements DataSourceProviderInterface
{
    /**
     * Related data source code
     */
    const DATA_SOURCE_CODE = 'partial_update';


    /** Define the schema for the attribute */

     /**
     * @var LoggerInterface
     */
    private $logger;


    /**
     * Constructor.
     */
    public function __construct(LoggerInterface $logger) {
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
     * Add custom code here
     *
     * {@inheritdoc}
     */
    public function appendData($storeId, array $indexData)
    {
        foreach (array_keys($indexData) as $productId) {
            try {
                if ($productId != "fields"){
                        $indexData[$productId]['action'] = Config::OPERATION_TYPE_UPDATE;
                }
            } catch (\Exception $e) {
                $this->logger->error('Error while updating operation type for product -'.$productId. $e->__toString());
            }
        }
        
        return $indexData;
    }

}
                