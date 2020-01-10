<?php
/**
 * Copyright (c) 2019 Unbxd Inc.
 */

/**
 * Init development:
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 */
namespace Unbxd\ProductFeed\Block\Adminhtml\System\Config\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Unbxd\ProductFeed\Model\Feed\FileManagerFactory as FeedFileManagerFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Class ProductFeed
 * @package Unbxd\ProductFeed\Block\Adminhtml\System\Config\Field
 */
class ProductFeed extends Field
{
    /**
     * @var FeedFileManagerFactory
     */
    protected $feedFileManagerFactory;

    /**
     * @var TimezoneInterface
     */
    protected $dateTime;

    /**
     * @var \Unbxd\ProductFeed\Model\Feed\FileManager|null
     */
    private $feedFileManager = null;

    /**
     * ProductFeed constructor.
     * @param Context $context
     * @param FeedFileManagerFactory $feedFileManagerFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        FeedFileManagerFactory $feedFileManagerFactory,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $data
        );
        $this->feedFileManagerFactory = $feedFileManagerFactory;
        $this->dateTime = $context->getLocaleDate();
    }

    /**
     * @return \Unbxd\ProductFeed\Model\Feed\FileManager|null
     */
    protected function getFeedFileManager()
    {
        if (null === $this->feedFileManager) {
            /** @var \Unbxd\ProductFeed\Model\Feed\FileManager $feedFileManager */
            $feedFileManager = $this->feedFileManagerFactory->create();
            $feedFileManager->setIsConvertedToArchive(true);

            $this->feedFileManager = $feedFileManager;
        }

        return $this->feedFileManager;
    }

    /**
     * @return bool
     */
    protected function isFeedExist()
    {
        return $this->getFeedFileManager()->isExist();
    }
}