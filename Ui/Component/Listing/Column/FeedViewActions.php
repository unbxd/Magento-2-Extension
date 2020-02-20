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
namespace Unbxd\ProductFeed\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Unbxd\ProductFeed\Model\FeedView;

/**
 * Class FeedViewActions
 * @package Unbxd\ProductFeed\Ui\Component\Listing\Column
 */
class FeedViewActions extends Column
{
    /** Url path */
    const URL_PATH_VIEW = 'unbxd_productfeed/feed_view/viewDetails';
    const URL_PATH_DELETE = 'unbxd_productfeed/feed_view/delete';
    const URL_PATH_REPEAT = 'unbxd_productfeed/feed_view/repeat';

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var string
     */
    private $viewUrl;

    /**
     * @var string
     */
    private $deleteUrl;

    /**
     * @var string
     */
    private $repeatUrl;

    /**
     * FeedViewActions constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     * @param string $viewUrl
     * @param string $deleteUrl
     * @param string $repeatUrl
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = [],
        $viewUrl = self::URL_PATH_VIEW,
        $deleteUrl = self::URL_PATH_DELETE,
        $repeatUrl = self::URL_PATH_REPEAT
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->viewUrl = $viewUrl;
        $this->deleteUrl = $deleteUrl;
        $this->repeatUrl = $repeatUrl;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $name = $this->getData('name');
                if (isset($item['feed_id'])) {
                    $status = strtolower(strip_tags($item['status']));

                    $item[$name]['view'] = [
                        'href' => $this->urlBuilder->getUrl($this->viewUrl, ['id' => $item['feed_id']]),
                        'label' => __('View Details')
                    ];
                    if ($status == strtolower(FeedView::STATUS_ERROR_LABEL)) {
                        $item[$name]['repeat'] = [
                            'href' => $this->urlBuilder->getUrl($this->repeatUrl, ['id' => $item['feed_id']]),
                            'label' => __('Repeat'),
                            'confirm' => [
                                'title' => __('Repeat operation #%1', $item['feed_id']),
                                'message' => __('Are you sure you want to repeat this synchronization?
                                    <br/>Based on data, related to this operation, the new job will be added to the <strong>Indexing Queue</strong>.
                                    <br/>Current record <strong>(#%1)</strong> will be removed from <strong>Feed View</strong>. Continue?', $item['feed_id'])
                            ]
                        ];
                    }
                    if ($status != strtolower(FeedView::STATUS_RUNNING_LABEL)) {
                        $item[$name]['delete'] = [
                            'href' => $this->urlBuilder->getUrl($this->deleteUrl, ['id' => $item['feed_id']]),
                            'label' => __('Delete'),
                            'confirm' => [
                                'title' => __('Delete #%1', $item['feed_id']),
                                'message' => __('Are you sure you want to delete a record #%1?', $item['feed_id'])
                            ]
                        ];
                    }
                }
            }
        }

        return $dataSource;
    }
}
