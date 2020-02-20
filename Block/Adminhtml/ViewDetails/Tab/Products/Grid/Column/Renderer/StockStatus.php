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
namespace Unbxd\ProductFeed\Block\Adminhtml\ViewDetails\Tab\Products\Grid\Column\Renderer;

use Unbxd\ProductFeed\Block\Adminhtml\ViewDetails\Tab\Products\Grid\Column\Renderer;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class StockStatus
 * @package Unbxd\ProductFeed\Block\Adminhtml\ViewDetails\Tab\Products\Grid\Column\Renderer
 */
class StockStatus extends Renderer
{
    /**
     * Renders grid column
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $value = $row->getData($this->getColumn()->getIndex());
        $cell = '<span class="grid-severity-critical"><span>' . __('No') . '</span></span>';
        if ($value == \Magento\CatalogInventory\Model\Stock::STOCK_IN_STOCK) {
            $cell = '<span class="grid-severity-notice"><span>' . __('Yes') . '</span></span>';
        }
        return $cell;
    }
}
