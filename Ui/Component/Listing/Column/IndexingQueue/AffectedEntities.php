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
use Magento\Framework\Stdlib\StringUtils;
use Unbxd\ProductFeed\Model\IndexingQueue;
use Magento\Framework\UrlInterface;
use Magento\Framework\Filter\FilterManager;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;

/**
 * Class AffectedEntities
 * @package Unbxd\ProductFeed\Ui\Component\Listing\Column\IndexingQueue
 */
class AffectedEntities extends Column
{
    /**
     * Max links size for column
     */
    const MAX_LINKS = 30;

    /**
     * @var IndexingQueue
     */
    private $indexingQueue;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var FilterManager
     */
    protected $filterManager;

    /**
     * AffectedEntities constructor.
     * @param StringUtils $stringUtils
     * @param IndexingQueue $indexingQueue
     * @param UrlInterface $urlBuilder
     * @param FilterManager $filterManager
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        IndexingQueue $indexingQueue,
        UrlInterface $urlBuilder,
        FilterManager $filterManager,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
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
        $this->urlBuilder = $urlBuilder;
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
                $item['affected_entities'] = $this->decorateCell($item['affected_entities'], $item['queue_id']);
            }
        }

        return $dataSource;
    }

    /**
     * @param $value
     * @param $rowId
     * @return string
     */
    private function decorateCell($value, $rowId)
    {
        // link on full catalog (by default)
        $cell = '<a href="' . $this->getUrl('catalog/product/index') .'" target="_blank">' . $value .'</a>';
        if (strpos($value, '#') !== false) {
            // grab links for separate products
            $entityIds = array_map(function($item) {
                return trim($item, '#');
            }, explode(', ', $value));

            $links = [];
            foreach ($entityIds as $id) {
                $url = $this->getUrl('catalog/product/edit', ['id' => $id]);
                $links[] = '<a href="' . $url .'" target="_blank">' . '#' . $id .'</a>';
            }

            $cell = implode(', ', $links);
            if (count($links) > self::MAX_LINKS) {
                $cell = $this->formatCell($links, $rowId);
            }
        }

        return $cell;
    }

    /**
     * @param $links
     * @param $rowId
     * @return string
     */
    private function formatCell(array $links, $rowId)
    {
        $viewDetailsUrl = $this->getUrl('unbxd_productfeed/indexing_queue/viewDetails', ['id' => $rowId]);

        $links = array_slice($links, 0, self::MAX_LINKS);
        $cell = implode(', ', $links);

        $cell .= '...<br/>';
        $cell .= '<a href="' . $viewDetailsUrl .'" target="_blank">' . __('See Entities Details') . '</a>';

        return $cell;
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
