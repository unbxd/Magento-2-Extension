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
namespace Unbxd\ProductFeed\Model\IndexingQueue\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Unbxd\ProductFeed\Model\IndexingQueue;

/**
 * Class Status
 * @package Unbxd\ProductFeed\Model\Queue\Source
 */
class Status implements OptionSourceInterface
{
    /**
     * @var IndexingQueue
     */
    protected $indexingQueue;

    /**
     * Status constructor.
     * @param IndexingQueue $indexingQueue
     */
    public function __construct(
        IndexingQueue $indexingQueue
    ) {
        $this->indexingQueue = $indexingQueue;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $availableOptions = $this->indexingQueue->getAvailableStatuses();
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
