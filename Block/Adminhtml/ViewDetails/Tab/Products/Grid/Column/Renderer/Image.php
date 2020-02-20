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
 * Class Image
 * @package Unbxd\ProductFeed\Block\Adminhtml\Indexing\Queue\Item\Tab\Products\Grid\Column\Renderer
 */
class Image extends Renderer
{
    const IMAGE_ROLE = 'feed_adminhtml_grid_details_product_image';

    /**
     * Renders grid column
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $productId = $row->getData($this->getColumn()->getIndex());
        $product = $this->getProductById($productId);

        $url = $this->getImageUrl($product, self::IMAGE_ROLE);
        $imageHtml = '<img src="' . $url . '"/>';

        return $imageHtml;
    }

    /**
     * @param $product
     * @param $imageRole
     * @return string
     */
    protected function getImageUrl($product, $imageRole)
    {
        $imageFile = $product->getImage();
        $url = $imageFile
            ? $this->imageHelper
                ->init($product, $imageRole)
                ->setImageFile($imageFile)
                ->getUrl()
            : $this->imageHelper->getDefaultPlaceholderUrl('thumbnail');

        return $url;
    }

    /**
     * @param $itemId
     * @param string $imageRole
     * @return bool|\Magento\Catalog\Api\Data\ProductInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductForThumbnail($itemId, $imageRole = self::IMAGE_ROLE)
    {
        if (!$itemId) {
            return false;
        }

        $product = $this->getProductById($itemId);
        if ($product) {
            $placeholder = strpos($this->getImageUrl($product, $imageRole), 'placeholder');
            if ($placeholder != false) {
                $parentData = $this->getParentIdsByChild($product->getId());
                if (isset($parentData[0])) {
                    $product = $this->getProductById($parentData[0]);
                }
            }
        }

        return $product;
    }
}
