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
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\Filter\FilterManager;
use Magento\Framework\UrlInterface;

/**
 * Class FeedViewId
 * @package Unbxd\ProductFeed\Ui\Component\Listing\Column\IndexingQueue
 */
class FeedViewId extends Column
{
    /**
     * @var FilterManager
     */
    protected $filterManager;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * FeedViewId constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param FilterManager $filterManager
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        FilterManager $filterManager,
        UrlInterface $urlBuilder,
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
        $this->urlBuilder = $urlBuilder;
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
                $feedViewId = $item['feed_view_id'];
                if ($feedViewId) {
                    $item['feed_view_id'] = $this->formatValue($feedViewId);
                }

            }
        }

        return $dataSource;
    }

    /**
     * @param int $id
     * @return string
     */
    private function formatValue($id)
    {
        $viewDetailsUrl = $this->getUrl('unbxd_productfeed/feed_view/viewDetails', ['id' => $id]);
        $cellHtml = '<a href="' . $viewDetailsUrl .'" target="_blank">' . $id . '</a>';

        return $cellHtml;
    }

    /**
     * Generate url by route and parameters
     *
     * @param string $route
     * @param array $params
     * @return string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->urlBuilder->getUrl($route, $params);
    }
}