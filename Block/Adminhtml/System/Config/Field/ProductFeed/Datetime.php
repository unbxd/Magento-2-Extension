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

/**
 * Class Datetime
 * @package Unbxd\ProductFeed\Block\Adminhtml\System\Config\Field\ProductFeed
 */
class Datetime extends ProductFeed
{
    /**
     * @inheritdoc
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        /** @var \Unbxd\ProductFeed\Model\Feed\FileManager $feedFileManager */
        $feedFileManager = $this->getFeedFileManager();

        $dateTime = '--------';
        if ($this->isFeedExist()) {
            $dateTime = date('Y-m-d H:i:s', $feedFileManager->getFileMtime());
            $dateTime = $this->dateTime->formatDate($dateTime, \IntlDateFormatter::MEDIUM, true);
        }

        return $dateTime;
    }
}