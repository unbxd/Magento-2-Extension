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
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\UrlInterface;
use Magento\Framework\Filter\FilterManager;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;

/**
 * Class AdditionalInformation
 * @package Unbxd\ProductFeed\Ui\Component\Listing\Column\FeedView
 */
class AdditionalInformation extends Column
{
    /**
     * Max string size for additional information column
     */
    const MAX_STRING_SIZE = 150;

    /**
     * @var StringUtils
     */
    private $string;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var FilterManager
     */
    protected $filterManager;

    /**
     * AdditionalInformation constructor.
     * @param StringUtils $string
     * @param UrlInterface $urlBuilder
     * @param FilterManager $filterManager
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        StringUtils $string,
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
        $this->string = $string;
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
                $item['additional_information'] = $this->decorateCell(
                    $item['additional_information'], $item['feed_id']
                );
            }
        }

        return $dataSource;
    }

    /**
     * @param $rowValue
     * @param $rowId
     * @return string
     */
    private function decorateCell($rowValue, $rowId)
    {
        $cell = $rowValue;
        if (is_string($cell) && (strlen($cell) > self::MAX_STRING_SIZE)) {
            $url = $this->getUrl('unbxd_productfeed/feed_view/viewDetails', ['id' => $rowId]);
            $cell = $this->string->substr($rowValue, 0, self::MAX_STRING_SIZE);
            $cell .= '...<br/>';
            $cell .= '<a href="' . $url .'" target="_blank">' . __('See View Details') . '</a>';
        }

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
