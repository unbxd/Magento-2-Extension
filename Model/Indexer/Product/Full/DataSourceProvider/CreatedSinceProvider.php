<?php

namespace Unbxd\ProductFeed\Model\Indexer\Product\Full\DataSourceProvider;

/**
 * @author      Jag S <jagadeesh@oceaniasolution.com>
 */

use Unbxd\ProductFeed\Model\Indexer\Product\Full\DataSourceProviderInterface;
use Unbxd\ProductFeed\Logger\LoggerInterface;

use Exception;

class CreatedSinceProvider implements DataSourceProviderInterface
{
    /**
     * Related data source code
     */
    const DATA_SOURCE_CODE = 'created_since';


    /** Define the schema for the attribute */

    /**
     * @var LoggerInterface
     */
    private $logger;



    /**
     * Constructor.
     */
    public function __construct( LoggerInterface $logger)
    {
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
        $now = date_create();
        foreach (array_keys($indexData) as $productId) {
            try {
                if ($productId != "fields") {
                    $createdIn = $indexData[$productId]["created_at"];
                    if ($createdIn) {
                        $indexData[$productId]["createdSinceInDays"] = date_diff(date_create($createdIn), $now)->format("%a");
                    }
                }
            } catch (\Exception $e) {
                $this->logger->error('Error while saleable quantitys product -' . $productId . $e->__toString());
            }
        }
        $this->addIndexedFields($indexData, "createdSinceInDays", "number");
        return $indexData;
    }

    /**
     * @param $indexData
     * @return 
     */
    private function addIndexedFields(array &$indexData, $attrName, $fieldType = "number")
    {
        $alreadyExistFields = array_key_exists('fields', $indexData) ? $indexData['fields'] : [];
        $indexData['fields'] = array_merge($alreadyExistFields, [$attrName => [
            'fieldName' => $attrName,
            'dataType' => $fieldType,
            'multiValued' => false,
            'autoSuggest' => false
        ]]);
    }
}
