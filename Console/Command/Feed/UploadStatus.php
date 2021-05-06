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

use Magento\Store\Model\Store;
use Unbxd\ProductFeed\Console\Command\Feed\AbstractCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unbxd\ProductFeed\Api\Data\FeedViewInterface;
use Unbxd\ProductFeed\Model\Feed\Config as FeedConfig;
use Unbxd\ProductFeed\Model\Feed\Api\Connector as ApiConnector;
use Unbxd\ProductFeed\Model\Feed\Api\Response as FeedResponse;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class UploadStatus
 * @package Unbxd\ProductFeed\Console\Command\Feed
 */
class UploadStatus extends AbstractCommand
{
    /**
     * Type option key
     */
    const TYPE_INPUT_OPTION_KEY = 'type';

    /**
     * Type values
     */
    const TYPE_FULL = 1;
    const TYPE_INCREMENTAL = 2;

    /**
     * Upload ID argument key
     */
    const UPLOAD_ID_ARGUMENT_KEY = 'upload_id';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('unbxd:product-feed:upload-status')
            ->setDescription('Check upload status for provided upload ID (if empty last upload ID will be used)')
            ->addOption(
                self::STORE_INPUT_OPTION_KEY,
                's',
                InputOption::VALUE_REQUIRED,
                'Use the specific Store View',
                Store::DEFAULT_STORE_ID
            )
            ->addOption(
                self::TYPE_INPUT_OPTION_KEY,
                't',
                InputOption::VALUE_REQUIRED,
                sprintf(
                    'Upload type: full (%s) or incremental (%s)',
                    self::TYPE_FULL,
                    self::TYPE_INCREMENTAL
                ),
                self::TYPE_FULL
            )
            ->addArgument(
                self::UPLOAD_ID_ARGUMENT_KEY,
                InputArgument::OPTIONAL,
                'Feed Upload ID'
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

        $storeId = $input->getOption(self::STORE_INPUT_OPTION_KEY) ?: 1;

        if (!$this->feedHelper->isAuthorizationCredentialsSetup($storeId)) {
            $output->writeln("<error>Please check authorization credentials to perform this operation.</error>");
            return false;
        }

        $this->preProcessActions($output);

        $uploadId = $input->getArgument(self::UPLOAD_ID_ARGUMENT_KEY);
        if (!$uploadId) {
            $uploadId = $this->feedHelper->getLastUploadId();
        }

        $type = FeedConfig::FEED_TYPE_FULL_UPLOADED_STATUS;
        if ($input->getOption(self::TYPE_INPUT_OPTION_KEY) == self::TYPE_INCREMENTAL) {
            $type = FeedConfig::FEED_TYPE_INCREMENTAL_UPLOADED_STATUS;
        }

        /** @var ApiConnector $connectorManager */
        $connectorManager = $this->getConnectorManager();
        try {
            $connectorManager->resetHeaders()
                ->resetParams()
                ->setExtraParams([FeedViewInterface::UPLOAD_ID => $uploadId])
                ->execute($type, \Zend_Http_Client::GET, [], [], $storeId);
        } catch (\Exception $e) {
            throw new \Exception(__($e->getMessage()));
        }

        $this->buildResponse($output, $connectorManager, $uploadId, $storeId);

        $this->postProcessActions($output);

        return true;
    }

    /**
     * @param OutputInterface $output
     * @param $connectorManager
     * @param $uploadId
     * @param null $storeId
     * @return $this
     */
    private function buildResponse(OutputInterface $output, $connectorManager, $uploadId, $storeId = null)
    {
        /** @var FeedResponse $response */
        $response = $connectorManager->getResponse();
        if ($response instanceof FeedResponse) {
            $responseBodyData = $response->getResponseBodyAsArray();
            if (!empty($responseBodyData)) {
                $status = array_key_exists(FeedResponse::RESPONSE_FIELD_STATUS, $responseBodyData)
                    ? $responseBodyData[FeedResponse::RESPONSE_FIELD_STATUS]
                    : null;

                if (!$status) {
                    $output->writeln("<error>Please make sure your request is correct. Possible reason: the last type of synchronization does not match the current request type.</error>");
                    return $this;
                }

                $message = '';
                if ($status == FeedResponse::RESPONSE_FIELD_STATUS_VALUE_INDEXING) {
                    $message = FeedConfig::FEED_MESSAGE_BY_RESPONSE_TYPE_INDEXING;
                } else if ($status == FeedResponse::RESPONSE_FIELD_STATUS_VALUE_INDEXED) {
                    $message = FeedConfig::FEED_MESSAGE_BY_RESPONSE_TYPE_COMPLETE;
                } else if ($status == FeedResponse::RESPONSE_FIELD_STATUS_VALUE_FAILED) {
                    $affectedStoreId = $storeId ?: $this->getDefaultStoreId();
                    $message = sprintf(FeedConfig::FEED_MESSAGE_BY_RESPONSE_TYPE_ERROR, $affectedStoreId);
                }

                $message = strip_tags($message);
                try {
                    $rows = [];
                    $rows[] = [$uploadId, $status, $message];
                    $table = $this->getHelperSet()->get('table');
                    $table->setHeaders(['Upload ID', 'Status', 'Message'])
                        ->addRows($rows)
                        ->render($output);
                } catch (\Exception $e) {
                    // for versions that don't support helper 'table'
                    $output->writeln("<info>Upload ID:    {$uploadId}</info>");
                    $output->writeln("<info>Status:       {$status}</info>");
                    $output->writeln("<info>Message:      {$message}</info>");
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
        $this->flushCache();

        return $this;
    }
}