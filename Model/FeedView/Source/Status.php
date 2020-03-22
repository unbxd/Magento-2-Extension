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
namespace Unbxd\ProductFeed\Model\FeedView\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Unbxd\ProductFeed\Model\Feed\Config as FeedConfig;

/**
 * Class Status
 * @package Unbxd\ProductFeed\Model\Queue\Source
 */
class Status implements OptionSourceInterface
{
    /**
     * @var FeedConfig
     */
    protected $feedConfig;

    /**
     * Status constructor.
     * @param FeedConfig $feedConfig
     */
    public function __construct(
        FeedConfig $feedConfig
    ) {
        $this->feedConfig = $feedConfig;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $availableOptions = $this->feedConfig->getAvailableOperationTypes();
        $options = [];
        foreach ($availableOptions as $key => $value) {
            $options[] = [
                'label' => $value,
                'value' => $key,
            ];
        }
        return $options;
    }
}
