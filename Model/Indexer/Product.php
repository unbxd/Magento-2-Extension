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
namespace Unbxd\ProductFeed\Model\Indexer;

use Magento\Framework\Indexer\IndexerRegistry;
use Unbxd\ProductFeed\Model\Indexer\Product\Full\Action\Full as FullAction;
use Unbxd\ProductFeed\Model\IndexingQueue;
use Unbxd\ProductFeed\Model\IndexingQueue\Handler as QueueHandler;
use Unbxd\ProductFeed\Model\Feed\Manager as FeedManager;
use Unbxd\ProductFeed\Helper\Data as HelperData;
use Unbxd\ProductFeed\Helper\ProductHelper;
use Unbxd\ProductFeed\Logger\LoggerInterface;
use Unbxd\ProductFeed\Logger\OptionsListConstants;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Message\ManagerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Magento\Framework\Indexer\Dimension;
use Magento\Framework\Indexer\DimensionFactory;

/**
 * Class Product
 * @package Unbxd\ProductFeed\Model\Indexer
 */
class Product implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    /**
     * Indexer ID in configuration
     */
    const INDEXER_ID = 'unbxd_products';

    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * @var FullAction
     */
    private $fullAction;

    /**
     * @var QueueHandler
     */
    private $queueHandler;

    /**
     * @var FeedManager
     */
    private $feedManager;

    /**
     * @var HelperData
     */
    private $helperData;

    /**
     * @var ProductHelper
     */
    private $productHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var ConsoleOutput
     */
    private $consoleOutput;

    /**
     * @var DimensionFactory
     */
    private $dimensionFactory;

    /**
     * Product constructor.
     * @param IndexerRegistry $indexerRegistry
     * @param FullAction $fullAction
     * @param QueueHandler $queueHandler
     * @param FeedManager $feedManager
     * @param HelperData $helperData
     * @param ProductHelper $productHelper
     * @param LoggerInterface $logger
     * @param StoreManagerInterface $storeManager
     * @param ManagerInterface $messageManager
     * @param ConsoleOutput $consoleOutput
     * @param DimensionFactory $dimensionFactory
     */
    public function __construct(
        IndexerRegistry $indexerRegistry,
        FullAction $fullAction,
        QueueHandler $queueHandler,
        FeedManager $feedManager,
        HelperData $helperData,
        ProductHelper $productHelper,
        LoggerInterface $logger,
        StoreManagerInterface $storeManager,
        ManagerInterface $messageManager,
        ConsoleOutput $consoleOutput,
        DimensionFactory $dimensionFactory
    ) {
        $this->indexerRegistry = $indexerRegistry;
        $this->fullAction = $fullAction;
        $this->queueHandler = $queueHandler;
        $this->feedManager = $feedManager;
        $this->helperData = $helperData;
        $this->productHelper = $productHelper;
        $this->logger = $logger->create(OptionsListConstants::LOGGER_TYPE_INDEXING);
        $this->storeManager = $storeManager;
        $this->messageManager = $messageManager;
        $this->consoleOutput = $consoleOutput;
        $this->dimensionFactory = $dimensionFactory;
    }

    /**
     * Execute materialization on ids entities
     * Used by mview, allows process indexer in the "Update on schedule" mode
     *
     * @param array $ids
     * @return bool
     * @throws \Exception
     */
    public function execute($ids = [])
    {
        // check if authorization credentials were provided
        if (!$this->helperData->isAuthorizationCredentialsSetup()) {
            $message = 'Please check authorization credentials to perform this operation.';
            $this->logger->error($message);
            // for console action(s)
            if (php_sapi_name() === 'cli') {
                $this->consoleOutput->writeln("<error>{$message}</error>");
                return false;
            }
            // for frontend action(s)
            $this->messageManager->addWarningMessage(__($message));
            return false;
        }

        // detect reindex action type
        $reindexType = IndexingQueue::TYPE_REINDEX_ROW;
        if (empty($ids)) {
            // full reindex (clean index, save new index)
            $reindexType = IndexingQueue::TYPE_REINDEX_FULL;
        }
        if (count($ids) > 1) {
            // list reindex (delete index record(s), save index record(s))
            $reindexType = IndexingQueue::TYPE_REINDEX_LIST;
        }

        if ($reindexType == IndexingQueue::TYPE_REINDEX_FULL) {
            $this->executeAction($ids, $reindexType, 1);
        } else {
            // for each store only in case if row or list reindex
            foreach ($this->getDimensions() as $dimension) {
                /** @var Dimension $dimension */
                $this->executeAction($ids, $reindexType, $dimension->getValue());
            }
        }
    }

    /**
     * @param $ids
     * @param $reindexType
     * @param $storeId
     * @return bool
     * @throws \Exception
     */
    private function executeAction($ids, $reindexType, $storeId)
    {
        // if run reindex via command line it will take all of the catalog product data and do reindex
        // in our case full catalog synchronization will be only available manually from backend
        // (Stores -> Configuration -> Unbxd -> Catalog -> Manual Synchronization)
        // or via separate cli command - php bin/magento unbxd:product-feed:full (or incremental if it's related
        // with separate products), so to prevent duplicate full catalog synchronization we just omit this operation.
        // NOTE*: empty ids means that the full catalog product must be reindex.
        if (empty($ids) && (php_sapi_name() === 'cli')) {
            return true;
        }

        // check if in indexing queue enabled
        if (!$this->helperData->isIndexingQueueEnabled($storeId)) {
            try {
                $this->logger->error('Indexing queue is disabled. START reindex.')->startTimer();
                $index = $this->fullAction->rebuildProductStoreIndex($storeId, $ids);
                $this->logger->info('END reindex. STATS:')->logStats();
            } catch (\Exception $e) {
                $this->logger->error('Indexing error.')->critical($e);
                if (php_sapi_name() === 'cli') {
                    $this->consoleOutput->writeln("<error>Indexing error: {$e->getMessage()}</error>");
                }

                return false;
            }

            $this->feedManager->execute($index);

            return true;
        }

        $this->queueHandler->add($ids, $reindexType, $storeId);

        return true;
    }

    /**
     * Execute full indexation
     * Will take all of the data and reindex
     * Will run when reindex via command line
     *
     * @throws \Exception
     */
    public function executeFull()
    {
        $this->execute();
    }

    /**
     * Execute partial indexation by ID list
     * Works with a set of entity changed (may be massaction)
     *
     * @param array $ids
     * @throws \Exception
     */
    public function executeList(array $ids)
    {
        $this->execute($ids);
    }

    /**
     * Execute partial indexation by ID
     * Works in runtime for a single entity using plugins
     *
     * @param int $id
     * @throws \Exception
     */
    public function executeRow($id)
    {
        if (!is_array($id)) {
            $id = [$id];
        }
        $this->execute($id);
    }

    /**
     * @return array
     */
    private function getDimensions()
    {
        $dimensions = [];
        $storeIds = array_keys($this->storeManager->getStores());
        foreach ($storeIds as $storeId) {
            if (class_exists(Dimension::class)) {
                $dimensions[] = $this->dimensionFactory->create('scope', (string) $storeId);
            } else {
                $dimension = new \Magento\Framework\DataObject();
                $dimension->setName('scope');
                $dimension->setValue($storeId);
                $dimensions[] = $dimension;
            }
        }

        return $dimensions;
    }
}