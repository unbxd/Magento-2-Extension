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
use Magento\Store\Model\Store;
use Unbxd\ProductFeed\Model\Feed\FileManager as FeedFileManager;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Download
 * @package Unbxd\ProductFeed\Console\Command\Feed
 */
class Download extends AbstractCommand
{
    /**#@+
     * Constant for current command.
     */
    const COMMAND = 'unbxd:product-feed:download';
    /**#@-*/

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(self::COMMAND)
            ->setDescription('Generate full catalog product feed for download.')
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
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool|int|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initAreaCode();

        $storeId = $input->getOption(self::STORE_INPUT_OPTION_KEY);
        if (!$storeId) {
            $storeId = $this->getDefaultStoreId();
        }

        // check authorization credentials
        if (!$this->feedHelper->isAuthorizationCredentialsSetup($storeId)) {
            $output->writeln("<error>Please check authorization credentials to perform this operation.</error>");
            return false;
        }

        if($this->feedHelper->isMultiPartUploadEnabled($storeId)){
            $output->writeln("<error>Feed download option is not support when multi part upload is enabled.</error>");
            return false;
        }

        // check if catalog product not empty
        $productIds = $this->productHelper->getAllProductsIds();
        if (!count($productIds)) {
            $output->writeln("<error>There are no products to perform this operation.</error>");
            return false;
        }

        // pre process actions
        $this->preProcessActions($output, $storeId);

        $start = microtime(true);
        try {
            $output->writeln("<info>Rebuild index...</info>");
            $index = $this->reindexAction->rebuildProductStoreIndex($storeId, [],null,$this->getFeedManager());
        } catch (\Exception $e) {
            $output->writeln("<error>Indexing error: {$e->getMessage()}</error>");
            return false;
        }

        if (empty($index)) {
            $output->writeln("<error>Index data is empty. Possible reason: product(s) with status 'Disabled' were performed.</error>");
            return false;
        }

        try {
            $output->writeln("<info>Generate feed...</info>");
            $this->getFeedManager()->executeForDownload($index, $storeId);
        } catch (\Exception $e) {
            $output->writeln("<error>Feed generation error: {$e->getMessage()}</error>");
            return false;
        }

        // post process actions
        $this->postProcessActions($output, $storeId, $start);
    }

    /**
     * @param OutputInterface $output
     * @param null $storeId
     * @return $this|\Unbxd\ProductFeed\Console\Command\Feed\AbstractCommand
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function preProcessActions($output, $storeId = null)
    {
        $storeName = $this->getStoreNameById($storeId);
        $output->writeln("<info>Performing operations for store with ID {$storeId} ({$storeName}):</info>");

        return $this;
    }

    /**
     * @param OutputInterface $output
     * @param null $storeId
     * @param null $start
     * @return $this|\Unbxd\ProductFeed\Console\Command\Feed\AbstractCommand
     */
    protected function postProcessActions($output, $storeId = null, $start = null)
    {
        if ($path = $this->getFeedPath($output, $storeId)) {
            $output->writeln("<info>Product feed has been successfully generated.</info>");
            $output->writeln("<info>Can be download by following path: {$path}.</info>");
        }

        $end = microtime(true);
        $workingTime = round($end - $start, 2);
        $output->writeln("<info>Working time: {$workingTime}</info>");

        $this->flushCache();

        return $this;
    }

    /**
     * @param $output
     * @param null $storeId
     * @return bool|string
     */
    private function getFeedPath($output, $storeId = null)
    {
        /** @var FeedFileManager $feedFileManager */
        $feedFileManager = $this->getFeedFileManager(
            [
                'subDir' => FeedFileManager::DEFAULT_SUB_DIR_FOR_DOWNLOAD,
                'store' => sprintf('%s%s', FeedFileManager::STORE_PARAMETER, $storeId)
            ]
        );
        $feedFileManager->setIsConvertedToArchive(true);
        if (!$feedFileManager->isExist()) {
            $output->writeln("<error>Generated feed doesn't exist.</error>");
            return false;
        }

        return $feedFileManager->getFileLocation();
    }
}