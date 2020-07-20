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
namespace Unbxd\ProductFeed\Console\Command\Feed;

use Unbxd\ProductFeed\Console\Command\Feed\AbstractCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unbxd\ProductFeed\Model\CronManager;
use Unbxd\ProductFeed\Model\Feed\Config as FeedConfig;
use Magento\Store\Model\Store;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Full
 * @package Unbxd\ProductFeed\Console\Command\Feed
 */
class Full extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('unbxd:product-feed:full')
            ->setDescription('Full catalog product synchronization with Unbxd service.')
            ->addOption(
                self::STORE_INPUT_OPTION_KEY,
                's',
                InputOption::VALUE_REQUIRED,
                'Use the specific Store View',
                Store::DEFAULT_STORE_ID
            );

        parent::configure();
    }

    /**
     * Try to set area code in case if it was not set before
     *
     * @return $this
     */
    private function initAreaCode()
    {
        try {
            $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);
        } catch (LocalizedException $e) {
            // area code already set
        }

        return $this;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool|int|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initAreaCode();

        $storeId = $input->getOption(self::STORE_INPUT_OPTION_KEY);
        $stores = [$this->getDefaultStoreId()];
        if ($storeId) {
            // in case if store code was passed instead of store id
            if (!is_numeric($storeId)) {
                $storeId = $this->getStoreIdByCode($storeId, $stores);
            }
            $stores = [$storeId];
        }

        // check authorization credentials
        if (!$this->feedHelper->isAuthorizationCredentialsSetup($storeId)) {
            $output->writeln("<error>Please check authorization credentials to perform this operation.</error>");
            return false;
        }

        // check if related cron process doesn't occur to this process to prevent duplicate execution
        $jobs = $this->getCronManager()->getRunningSchedules(CronManager::FEED_JOB_CODE_UPLOAD);
        if ($jobs->getSize()) {
            $message = 'At the moment, the cron job is already executing this process. '. "\n" . 'To prevent duplicate process, which will increase the load on the server, please try it later.';
            $output->writeln("<error>{$message}</error>");
            return false;
        }

        // check if catalog product not empty
        $productIds = $this->productHelper->getAllProductsIds();
        if (!count($productIds)) {
            $output->writeln("<error>There are no products to perform this operation.</error>");
            return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
        }

        // pre process actions
        $this->preProcessActions($output);

        $errors = [];
        $start = microtime(true);
        if (!empty($stores)) {
            foreach ($stores as $storeId) {
                $storeName = $this->getStoreNameById($storeId);
                $output->writeln("<info>Performing operations for store with ID {$storeId} ({$storeName}):</info>");
                /** @var \Magento\Store\Model\Store $store */
                try {
                    $output->writeln("<info>Rebuild index...</info>");
                    $index = $this->reindexAction->rebuildProductStoreIndex($storeId, []);
                } catch (\Exception $e) {
                    $output->writeln("<error>Indexing error: {$e->getMessage()}</error>");
                    $errors[$storeId] = $e->getMessage();
                    break;
                }

                if (empty($index)) {
                    $output->writeln("<error>Index data is empty. Possible reason: product(s) with status 'Disabled' were performed.</error>");
                    return false;
                }

                try {
                    $output->writeln("<info>Execute feed...</info>");
                    $this->getFeedManager()->execute($index, FeedConfig::FEED_TYPE_FULL, $storeId);
                } catch (\Exception $e) {
                    $output->writeln("<error>Feed execution error: {$e->getMessage()}</error>");
                    $errors[$storeId] = $e->getMessage();
                    break;
                }
            }
        }

        // post process actions
        $this->postProcessActions($output);

        $this->buildResponse($output, $stores, $errors);

        $end = microtime(true);
        $workingTime = round($end - $start, 2);
        $output->writeln("<info>Working time: {$workingTime}</info>");

        return true;
    }

    /**
     * @param OutputInterface $output
     * @param $stores
     * @param $errors
     * @return $this
     */
    private function buildResponse($output, $stores, $errors)
    {
        $errorMessage = strip_tags(FeedConfig::FEED_MESSAGE_BY_RESPONSE_TYPE_ERROR);
        if (!empty($errors)) {
            $affectedIds = implode(',', array_keys($errors));
            $errorMessages = implode(',', array_values($errors));
            $errorMessage = sprintf($errorMessage, $affectedIds . '. ' . $errorMessages);
            $output->writeln("<error>{$errorMessage}</error>");
        } else if ($this->feedHelper->isLastSynchronizationSuccess()) {
            $output->writeln("<info>" . FeedConfig::FEED_MESSAGE_BY_RESPONSE_TYPE_COMPLETE . "</info>");
        } else if ($this->feedHelper->isLastSynchronizationProcessing()) {
            $output->writeln("<info>" . strip_tags(FeedConfig::FEED_MESSAGE_BY_RESPONSE_TYPE_INDEXING) . "</info>");
        } else {
            $output->writeln("<info>" . strip_tags(FeedConfig::FEED_MESSAGE_BY_RESPONSE_TYPE_RUNNING) . "</info>");
        }

        return $this;
    }

    /**
     * @param OutputInterface $output
     * @return $this
     */
    protected function preProcessActions($output)
    {
        return $this;
    }

    /**
     * @param OutputInterface $output
     * @return $this
     */
    protected function postProcessActions($output)
    {
        $this->flushCache();

        return $this;
    }
}