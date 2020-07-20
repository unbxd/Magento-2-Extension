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

use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\Store;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Unbxd\ProductFeed\Console\Command\Feed\AbstractCommand;
use Unbxd\ProductFeed\Model\CronManager;
use Unbxd\ProductFeed\Model\Feed\Config as FeedConfig;

/**
 * Class IncrementalFromDate
 * @package Unbxd\ProductFeed\Console\Command\Feed
 */
class IncrementalFromDate extends AbstractCommand
{
    /**
     * @var bool
     */
    private $buildResponse = false;

    /**
     * Products last updated date argument key
     */
    const FROM_DATE_ARGUMENT_KEY = 'from_date';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('unbxd:product-feed:incremental-from-date')
            ->setDescription('Incremental catalog product(s) synchronization with Unbxd service from specific date.')
            ->addArgument(
                self::FROM_DATE_ARGUMENT_KEY,
                InputArgument::OPTIONAL,
                'From the date after which the products will be processed (date format Y-m-d H:i:s).
                Optional, if no date is specified, the last synchronization date will be used.'
            )
            ->addOption(
                self::STORE_INPUT_OPTION_KEY,
                's',
                InputOption::VALUE_OPTIONAL,
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

        $stores = [$this->getDefaultStoreId()];
        $storeId = $input->getOption(self::STORE_INPUT_OPTION_KEY);
        if ($storeId) {
            // in case if store code was passed instead of store ID
            if (!is_numeric($storeId)) {
                $storeId = $this->getStoreIdByCode($storeId, $stores);
            }
            $stores = [$storeId];
        } else {
            $stores = array_keys($this->storeManager->getStores());
        }

        // check authorization credentials
        foreach ($stores as $key => $value) {
            if (!$this->feedHelper->isAuthorizationCredentialsSetup($value)) {
                unset($stores[$key]);
            }
        }

        if (empty($stores)) {
            $output->writeln("<error>Please check authorization credentials to perform this operation.</error>");
        }
        // check if related cron process doesn't occur to this process to prevent duplicate execution
        $jobs = $this->getCronManager()->getRunningSchedules(CronManager::FEED_JOB_CODE_UPLOAD);
        if ($jobs->getSize()) {
            $message = 'At the moment, the cron job is already executing this process. ' . "\n" . 'To prevent duplicate process, which will increase the load on the server, please try it later.';
            $output->writeln("<error>{$message}</error>");
            return false;
        }

        // pre process actions
        $this->preProcessActions($output);

        $errors = [];
        $start = microtime(true);
        foreach ($stores as $storeId) {
            $storeName = $this->getStoreNameById($storeId);
            $output->writeln("<info>Performing operations for store with ID {$storeId} ({$storeName}):</info>");
            // validate provided date before perform
            $fromDate = $input->getArgument(self::FROM_DATE_ARGUMENT_KEY);
            if ($this->isDateValid($fromDate)) {
                $output->writeln("<info>From date: {$fromDate}</info>");
                $this->uploadFromDate($storeId,$fromDate);
            }else{
                $output->writeln("<info>Process Indexing Queue Entries...</info>");
                $this->getCronManager()->uploadFeed($storeId);
            }

            $this->buildResponse = true;
        }

        // post process actions
        $this->postProcessActions($output, $stores, $errors, $start);

        return true;
    }

    private function uploadFromDate($storeId, $fromDate)
    {

        $qty = count($this->productHelper->getAllProductsIds($storeId, $fromDate));
        if (!$qty) {
            $output->writeln("<info>There are no products to perform this operation.</info>");
            return;
        }
        $output->writeln("<info>Detected entities: {$qty}</info>");

        try {
            $output->writeln("<info>Rebuild index...</info>");
            $index = $this->reindexAction->rebuildProductStoreIndex($storeId, [], $fromDate);
        } catch (\Exception $e) {
            $output->writeln("<error>Indexing error: {$e->getMessage()}</error>");
            $errors[$storeId] = $e->getMessage();
            return;
        }

        if (empty($index)) {
            $output->writeln("<error>Index data is empty. Possible reason: product(s) with status 'Disabled' were performed.</error>");
            return;
        }

        try {
            $output->writeln("<info>Execute feed...</info>");
            $this->getFeedManager()->execute($index, FeedConfig::FEED_TYPE_INCREMENTAL, $storeId);
        } catch (\Exception $e) {
            $output->writeln("<error>Feed execution error: {$e->getMessage()}</error>");
            $errors[$storeId] = $e->getMessage();
            return;
        }
    }

    /**
     * @param null $date
     * @param string $format
     * @return bool
     */
    private function isDateValid(&$date = null, $format = 'Y-m-d H:i:s')
    {
        /* if (null == $date) {
        // if date is not specified, get the last synchronization date from config
        // flush cache to get actual last synchronization date
        $this->flushCache();
        $date = $this->feedHelper->getLastSynchronizationDatetime();
        }*/

        try {
            $createdDate = \DateTime::createFromFormat($format, $date);
        } catch (\Exception $e) {
            return false;
        }

        // the Y (4 digits year) returns TRUE for any integer with any number of digits
        // so changing the comparison from == to === fixes the issue.
        return (bool) ($createdDate && ($createdDate->format($format) === $date));
    }

    /**
     * @param OutputInterface $output
     * @param array $stores
     * @param array $errors
     * @return $this
     */
    private function buildResponse($output, $stores = [], $errors = [])
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
     * @return $this|\Unbxd\ProductFeed\Console\Command\Feed\AbstractCommand
     */
    protected function preProcessActions($output)
    {
        return $this;
    }

    /**
     * @param OutputInterface $output
     * @param array $stores
     * @param array $errors
     * @param null $start
     * @return $this|\Unbxd\ProductFeed\Console\Command\Feed\AbstractCommand
     */
    protected function postProcessActions($output, $stores = [], $errors = [], $start = null)
    {
        if ($this->buildResponse) {
            $this->buildResponse($output, $stores, $errors);
        }

        $end = microtime(true);
        $workingTime = round($end - $start, 2);
        $output->writeln("<info>Working time: {$workingTime}</info>");

        $this->flushCache();

        return $this;
    }
}
