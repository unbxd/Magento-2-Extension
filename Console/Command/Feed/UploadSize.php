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
use Unbxd\ProductFeed\Model\Feed\Config as FeedConfig;
use Unbxd\ProductFeed\Model\Feed\Api\Connector as ApiConnector;
use Unbxd\ProductFeed\Model\Feed\Api\Response as FeedResponse;
use Magento\Store\Model\Store;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class UploadSize
 * @package Unbxd\ProductFeed\Console\Command\Feed
 */
class UploadSize extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('unbxd:product-feed:upload-size')
            ->setDescription('Check upload size for provided store ID (if empty default will be used)')
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

        $storeId = $input->getOption(self::STORE_INPUT_OPTION_KEY) ?: 1;

        // check authorization credentials
        if (!$this->feedHelper->isAuthorizationCredentialsSetup($storeId)) {
            $output->writeln("<error>Please check authorization credentials to perform this operation.</error>");
            return false;
        }

        // pre process actions
        $this->preProcessActions($output);

        /** @var ApiConnector $connectorManager */
        $connectorManager = $this->getConnectorManager();
        try {
            $connectorManager->resetHeaders()
                ->resetParams()
                ->execute(FeedConfig::FEED_TYPE_UPLOADED_SIZE, \Zend_Http_Client::GET, [], [], $storeId);
        } catch (\Exception $e) {
            throw new \Exception(__($e->getMessage()));
        }

        $this->buildResponse($output, $connectorManager, $storeId);

        // post process actions
        $this->postProcessActions($output);

        return true;
    }

    /**
     * @param OutputInterface $output
     * @param $connectorManager
     * @param $storeId
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function buildResponse(OutputInterface $output, $connectorManager, $storeId)
    {
        /** @var FeedResponse $response */
        $response = $connectorManager->getResponse();
        if ($response instanceof FeedResponse) {
            if ($size = $response->getUploadedSize()) {
                $additionalMessage = 'Children products are not counted';
                $storeName = $this->getStoreNameById($storeId);

                try {
                    $rows = [];
                    $rows[] = [$storeName, $size, $additionalMessage];
                    $table = $this->getHelperSet()->get('table');
                    $table->setHeaders(['Store', 'Size', 'Additional Message'])
                        ->addRows($rows)
                        ->render($output);
                } catch (\Exception $e) {
                    // for versions that don't support helper 'table'
                    $output->writeln("<info>Store:                {$storeName}</info>");
                    $output->writeln("<info>Size:                 {$size}</info>");
                    $output->writeln("<info>Additional Message:   {$additionalMessage}</info>");
                }
            }
        }

        $connectorManager->resetResponse();

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
        $this->flushSystemConfigCache();

        return $this;
    }
}