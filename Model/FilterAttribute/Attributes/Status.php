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
namespace Unbxd\ProductFeed\Model\FilterAttribute\Attributes;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status as SourceStatus;
use Unbxd\ProductFeed\Model\FilterAttribute\FilterAttributeInterface;

/**
 * Class Status
 * @package Unbxd\ProductFeed\Model\FilterAttribute\Attributes
 */
class Status implements FilterAttributeInterface
{
    /**
     * Constant for attribute code
     */
    const ATTRIBUTE_CODE = ProductInterface::STATUS;

    /**
     * @var SourceStatus
     */
    protected $sourceStatus;

    /**
     * Status constructor.
     * @param SourceStatus $sourceStatus
     */
    public function __construct(
        SourceStatus $sourceStatus
    ) {
        $this->sourceStatus = $sourceStatus;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeCode()
    {
        return self::ATTRIBUTE_CODE;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue()
    {
        return SourceStatus::STATUS_DISABLED;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return $this->sourceStatus->getOptionText($this->getValue());
    }
}