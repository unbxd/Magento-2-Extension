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
namespace Unbxd\ProductFeed\Model\Admin;

use Magento\Framework\Notification\MessageInterface;
use Unbxd\ProductFeed\Helper\Feed as FeedHelper;
use Unbxd\ProductFeed\Model\Feed\FileManager as FeedFileManager;
use Unbxd\ProductFeed\Model\Feed\FileManagerFactory as FeedFileManagerFactory;
use Magento\Backend\Model\UrlInterface;

/**
 * Class ModuleAvailabilityMessages
 * @package Unbxd\ProductFeed\Model\Admin
 */
class ProductFeedGeneratedMessages implements MessageInterface
{
    /**
     * @var FeedFileManagerFactory
     */
    private $feedFileManagerFactory;

    /**
     * @var FeedHelper
     */
    private $feedHelper;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var FeedFileManager|null
     */
    private $feedFileManager = null;

    /**
     * ProductFeedGeneratedMessages constructor.
     * @param FeedFileManagerFactory $feedFileManagerFactory
     * @param FeedHelper $feedHelper
     * @param UrlInterface $url
     */
    public function __construct(
        FeedFileManagerFactory $feedFileManagerFactory,
        FeedHelper $feedHelper,
        UrlInterface $url
    ) {
        $this->feedFileManagerFactory = $feedFileManagerFactory;
        $this->feedHelper = $feedHelper;
        $this->url = $url;
    }

    /**
     * @inheritdoc
     *
     */
    public function getIdentity()
    {
        return hash('sha256', 'PRODUCT_FEED_GENERATED_MESSAGE');
    }

    /**
     * @inheritdoc
     */
    public function isDisplayed()
    {
        return (bool) ($this->getIsGeneratedForDownload() && $this->isFeedFileExist());
    }

    /**
     * @inheritdoc
     */
    public function getText()
    {
        $message = '';
        $message .= '<strong>UNBXD:</strong> Catalog product feed was successfully generated - <a href="%s">download</a><br/>';
        $message .= 'You can also download it from <a href="%s">configuration</a> section';
        $message = sprintf(
            $message,
            $this->getDownloadLink(),
            $this->getConfigurationUrl()
        );

        return __($message);
    }

    /**
     * @inheritdoc
     */
    public function getSeverity()
    {
        return MessageInterface::SEVERITY_NOTICE;
    }

    /**
     * @return FeedFileManager|null
     */
    private function getFeedFileManager()
    {
        if (null === $this->feedFileManager) {
            /** @var FeedFileManager $feedFileManager */
            $feedFileManager = $this->feedFileManagerFactory->create();
            $feedFileManager->setIsConvertedToArchive(true);

            $this->feedFileManager = $feedFileManager;
        }

        return $this->feedFileManager;
    }

    /**
     * @return bool
     */
    private function isFeedFileExist()
    {
        return $this->getFeedFileManager()->isExist();
    }

    /**
     * @return bool
     */
    private function getIsGeneratedForDownload()
    {
        return $this->feedHelper->getIsGeneratedForDownload();
    }

    /**
     * @return string
     */
    private function getDownloadLink()
    {
        return $this->url->getUrl('unbxd_productfeed/feed/download');
    }

    /**
     * @return string
     */
    private function getConfigurationUrl()
    {
        return $this->url->getUrl('adminhtml/system_config/edit/section/unbxd_catalog');
    }
}