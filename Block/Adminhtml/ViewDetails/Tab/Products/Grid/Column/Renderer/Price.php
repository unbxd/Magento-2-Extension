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
 * Class Price
 * @package Unbxd\ProductFeed\Block\Adminhtml\ViewDetails\Tab\Products\Grid\Column\Renderer
 */
class Price extends Renderer
{
    /**
     * Renders grid column
     *
     * @param \Magento\Framework\DataObject $row
     * @return float|int|string
     * @throws \Exception
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $currentItemId = $row->getData($this->getColumn()->getIndex());
        $priceResult = 0;
        $currencyCode = $this->getCurrencyCode($row);
        if ($currentItemId) {
            /** @var \Magento\Catalog\Model\Product $productModel */
            $productModel = $this->productFactory->create()->load($currentItemId);
            $priceResult = $productModel->getFinalPrice();

            if (!$currencyCode) {
                return $priceResult;
            }

            $priceResult = floatval($priceResult) * $this->getRate($row);
            $priceResult = sprintf("%f", $priceResult);
            $priceResult = $this->localeCurrency->getCurrency($currencyCode)->toCurrency($priceResult);
        }

        return $priceResult;
    }

    /**
     * Returns currency code, false on error
     *
     * @return mixed
     */
    private function getCurrencyCode($row)
    {
        if ($code = $this->getColumn()->getCurrencyCode()) {
            return $code;
        }

        if ($code = $row->getData($this->getColumn()->getCurrency())) {
            return $code;
        }

        return $this->currencyLocator->getDefaultCurrency($this->_request);
    }

    /**
     * Get rate for current row, 1 by default
     *
     * @param \Magento\Framework\DataObject $row
     * @return float|int
     */
    private function getRate($row)
    {
        if ($rate = $this->getColumn()->getRate()) {
            return floatval($rate);
        }

        if ($rate = $row->getData($this->getColumn()->getRateField())) {
            return floatval($rate);
        }

        return $this->defaultBaseCurrency->getRate($this->getCurrencyCode($row));
    }
}
