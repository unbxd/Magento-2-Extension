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
 * Class ActionType
 * @package Unbxd\ProductFeed\Model\Queue\Source
 */
class ActionType implements OptionSourceInterface
{
    /**
     * @var IndexingQueue
     */
    protected $indexingQueue;

    /**
     * IsActive constructor.
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
        $availableOptions = $this->indexingQueue->getAvailableActionTypes();
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