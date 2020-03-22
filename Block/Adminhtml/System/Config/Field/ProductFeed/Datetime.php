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
 * Class Datetime
 * @package Unbxd\ProductFeed\Block\Adminhtml\System\Config\Field\ProductFeed
 */
class Datetime extends ProductFeed
{
    /**
     * @param AbstractElement $element
     * @return false|string
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        /** @var \Unbxd\ProductFeed\Model\Feed\FileManager $feedFileManager */
        $feedFileManager = $this->getFeedFileManager(
            [
                'subDir' => FeedFileManager::DEFAULT_SUB_DIR_FOR_DOWNLOAD,
                'store' => sprintf('%s%s', FeedFileManager::STORE_PARAMETER, $this->getCurrentStoreId())
            ]
        );

        $dateTime = '--------';
        if ($this->isFeedExist()) {
            $dateTime = date('Y-m-d H:i:s', $feedFileManager->getFileMtime());
            $dateTime = $this->dateTime->formatDate($dateTime, \IntlDateFormatter::MEDIUM, true);
        }

        return $dateTime;
    }
}