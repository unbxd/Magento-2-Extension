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
namespace Unbxd\ProductFeed\Block\Adminhtml\System\Config\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Unbxd\ProductFeed\Model\Feed\FileManagerFactory as FeedFileManagerFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

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
     * @var StoreManagerInterface
     */
    protected $storeManager;

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
        $this->storeManager = $context->getStoreManager();
    }

    /**
     * @param string $store
     * @return \Magento\Store\Api\Data\StoreInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getStore($store = null)
    {
        return $this->storeManager->getStore($store);
    }

    /**
     * @param null $store
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCurrentStoreId($store = null)
    {
        return $this->_request->getParam(Store::ENTITY, $this->getStore($store)->getId());
    }

    /**
     * @param array $data
     * @return \Unbxd\ProductFeed\Model\Feed\FileManager|null
     */
    protected function getFeedFileManager($data = [])
    {
        if (null === $this->feedFileManager) {
            /** @var \Unbxd\ProductFeed\Model\Feed\FileManager $feedFileManager */
            $feedFileManager = $this->feedFileManagerFactory->create($data);
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