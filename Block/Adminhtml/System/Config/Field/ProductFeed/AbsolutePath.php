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
namespace Unbxd\ProductFeed\Block\Adminhtml\System\Config\Field\ProductFeed;

use Unbxd\ProductFeed\Block\Adminhtml\System\Config\Field\ProductFeed;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Unbxd\ProductFeed\Model\Feed\FileManager as FeedFileManager;

/**
 * Class AbsolutePath
 * @package Unbxd\ProductFeed\Block\Adminhtml\System\Config\Field\ProductFeed
 */
class AbsolutePath extends ProductFeed
{
    /**
     * @param AbstractElement $element
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        /** @var FeedFileManager $feedFileManager */
        $feedFileManager = $this->getFeedFileManager(
            [
                'subDir' => FeedFileManager::DEFAULT_SUB_DIR_FOR_DOWNLOAD,
                'store' => sprintf('%s%s', FeedFileManager::STORE_PARAMETER, $this->getCurrentStoreId())
            ]
        );

        $absolutePath = '--------';
        if ($this->isFeedExist()) {
            $absolutePath = $feedFileManager->getFileLocation();
        }

        return $absolutePath;
    }
}