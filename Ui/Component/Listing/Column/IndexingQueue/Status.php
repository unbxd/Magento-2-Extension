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
namespace Unbxd\ProductFeed\Ui\Component\Listing\Column\IndexingQueue;

use Magento\Ui\Component\Listing\Columns\Column;
use Unbxd\ProductFeed\Model\IndexingQueue;
use Magento\Framework\Filter\FilterManager;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;

/**
 * Class Status
 * @package Unbxd\ProductFeed\Ui\Component\Listing\Column\IndexingQueue
 */
class Status extends Column
{
    /**
     * @var IndexingQueue
     */
    private $indexingQueue;

    /**
     * @var FilterManager
     */
    protected $filterManager;

    /**
     * Status constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param IndexingQueue $indexingQueue
     * @param FilterManager $filterManager
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        IndexingQueue $indexingQueue,
        FilterManager $filterManager,
        array $components = [],
        array $data = []
    ) {
        parent::__construct(
            $context,
            $uiComponentFactory,
            $components,
            $data
        );
        $this->indexingQueue = $indexingQueue;
        $this->filterManager = $filterManager;
    }

    /**
     * Prepare data source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $item['status'] = $this->decorateStatus($item['status']);
            }
        }

        return $dataSource;
    }

    /**
     * @param int $status
     * @return string
     */
    private function decorateStatus($status)
    {
        $availableStatuses = $this->indexingQueue->getAvailableStatuses();
        $decoratorClassPath = 'undefined';
        $title = 'Undefined';
        if (array_key_exists($status, $availableStatuses)) {
            $title = $availableStatuses[$status];
            if ($status == IndexingQueue::STATUS_PENDING) {
                $decoratorClassPath = 'pending';
            } elseif ($status == IndexingQueue::STATUS_RUNNING) {
                $decoratorClassPath = 'minor';
            } elseif ($status == IndexingQueue::STATUS_COMPLETE) {
                $decoratorClassPath = 'notice';
            } elseif ($status == IndexingQueue::STATUS_ERROR) {
                $decoratorClassPath = 'critical';
            } elseif ($status == IndexingQueue::STATUS_HOLD) {
                $decoratorClassPath = 'hold';
            }
        }

        $cell = '<span class="grid-severity-' . $decoratorClassPath .'"><span>' . __($title) . '</span></span>';

        return $cell;
    }
}
