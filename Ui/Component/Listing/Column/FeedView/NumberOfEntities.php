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
namespace Unbxd\ProductFeed\Ui\Component\Listing\Column\FeedView;

use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\Filter\FilterManager;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;

/**
 * Class NumberOfEntities
 * @package Unbxd\ProductFeed\Ui\Component\Listing\Column\FeedView
 */
class NumberOfEntities extends Column
{
    /**
     * @var FilterManager
     */
    protected $filterManager;

    /**
     * NumberOfEntities constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param FilterManager $filterManager
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
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
                $item['number_of_entities'] = $this->formatValue($item['number_of_entities']);
            }
        }

        return $dataSource;
    }

    /**
     * @param $value
     * @return string
     */
    private function formatValue($value)
    {
        return number_format($value, 0, '.', ' ');
    }
}
