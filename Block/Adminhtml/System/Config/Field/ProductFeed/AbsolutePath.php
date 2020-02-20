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
 * Class AbsolutePath
 * @package Unbxd\ProductFeed\Block\Adminhtml\System\Config\Field\ProductFeed
 */
class AbsolutePath extends ProductFeed
{
    /**
     * @inheritdoc
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        /** @var \Unbxd\ProductFeed\Model\Feed\FileManager $feedFileManager */
        $feedFileManager = $this->getFeedFileManager();

        $absolutePath = '--------';
        if ($this->isFeedExist()) {
            $absolutePath = $feedFileManager->getFileLocation();
        }

        return $absolutePath;
    }
}