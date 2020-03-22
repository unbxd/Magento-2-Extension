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
use Unbxd\ProductFeed\Model\IndexingQueue;

/**
 * Class FeedViewActions
 * @package Unbxd\ProductFeed\Ui\Component\Listing\Column
 */
class IndexingQueueActions extends Column
{
    /** Url path */
    const URL_PATH_VIEW = 'unbxd_productfeed/indexing_queue/viewDetails';
    const URL_PATH_HOLD = 'unbxd_productfeed/indexing_queue/hold';
    const URL_PATH_UNHOLD = 'unbxd_productfeed/indexing_queue/unhold';
    const URL_PATH_DELETE = 'unbxd_productfeed/indexing_queue/delete';
    const URL_PATH_REPEAT = 'unbxd_productfeed/indexing_queue/repeat';

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
    private $holdUrl;

    /**
     * @var string
     */
    private $unHoldUrl;

    /**
     * @var string
     */
    private $repeatUrl;

    /**
     * @var string
     */
    private $deleteUrl;

    /**
     * IndexingQueueActions constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     * @param string $viewUrl
     * @param string $holdUrl
     * @param string $unHoldUrl
     * @param string $repeatUrl
     * @param string $deleteUrl
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = [],
        $viewUrl = self::URL_PATH_VIEW,
        $holdUrl = self::URL_PATH_HOLD,
        $unHoldUrl = self::URL_PATH_UNHOLD,
        $repeatUrl = self::URL_PATH_REPEAT,
        $deleteUrl = self::URL_PATH_DELETE
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->viewUrl = $viewUrl;
        $this->holdUrl = $holdUrl;
        $this->unHoldUrl = $unHoldUrl;
        $this->repeatUrl = $repeatUrl;
        $this->deleteUrl = $deleteUrl;
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
                if (isset($item['queue_id'])) {
                    $status = strtolower(strip_tags($item['status']));
                    $item[$name]['view'] = [
                        'href' => $this->urlBuilder->getUrl($this->viewUrl, ['id' => $item['queue_id']]),
                        'label' => __('View Details')
                    ];
                    if ($status == strtolower(IndexingQueue::STATUS_PENDING_LABEL)) {
                        $item[$name]['hold'] = [
                            'href' => $this->urlBuilder->getUrl($this->holdUrl, ['id' => $item['queue_id']]),
                            'label' => __('Hold'),
                            'confirm' => [
                                'title' => __('Hold #%1', $item['queue_id']),
                                'message' => __('Are you sure you want to put on hold record #%1?', $item['queue_id'])
                            ]
                        ];
                    }
                    if ($status == strtolower(IndexingQueue::STATUS_HOLD_LABEL)) {
                        $item[$name]['unhold'] = [
                            'href' => $this->urlBuilder->getUrl($this->unHoldUrl, ['id' => $item['queue_id']]),
                            'label' => __('Unhold'),
                            'confirm' => [
                                'title' => __('Unhold #%1', $item['queue_id']),
                                'message' => __('Are you sure you want to unhold record #%1?', $item['queue_id'])
                            ]
                        ];
                    }
                    if ($status == strtolower(IndexingQueue::STATUS_ERROR_LABEL)) {
                        $item[$name]['repeat'] = [
                            'href' => $this->urlBuilder->getUrl($this->repeatUrl, ['id' => $item['queue_id']]),
                            'label' => __('Repeat'),
                            'confirm' => [
                                'title' => __('Repeat #%1', $item['queue_id']),
                                'message' => __(
                                    sprintf(
                                        'Are you sure you want to repeat operation #%s?<br/> This operation will be switched to \'Pending\' status.',
                                        $item['queue_id']
                                    )
                                )
                            ]
                        ];
                    }
                    if ($status != strtolower(IndexingQueue::STATUS_RUNNING_LABEL)) {
                        $item[$name]['delete'] = [
                            'href' => $this->urlBuilder->getUrl($this->deleteUrl, ['id' => $item['queue_id']]),
                            'label' => __('Delete'),
                            'confirm' => [
                                'title' => __('Delete #%1', $item['queue_id']),
                                'message' => __('Are you sure you want to delete record #%1?', $item['queue_id'])
                            ]
                        ];
                    }
                }
            }
        }

        return $dataSource;
    }
}
