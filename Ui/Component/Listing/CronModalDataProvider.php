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
namespace Unbxd\ProductFeed\Ui\Component\Listing;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Ui\Component;
use Magento\Framework\UrlInterface;
use Unbxd\ProductFeed\Model\ResourceModel\Cron\Collection;
use Unbxd\ProductFeed\Model\ResourceModel\Cron\CollectionFactory;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\App\RequestInterface;

/**
 * Class CronModalDataProvider
 * @package Unbxd\ProductFeed\Ui\Component\Listing
 */
class CronModalDataProvider extends AbstractDataProvider
{
    /**#@+
     * System config edit Layout handler
     */
    const SYSTEM_CONFIG_EDIT_LAYOUT_HANDLER = 'adminhtml_system_config_edit';
    /**#@-*/

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var AuthorizationInterface
     */
    protected $authorization;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * CronModalDataProvider constructor.
     * @param UrlInterface $urlBuilder
     * @param CollectionFactory $collectionFactory
     * @param AuthorizationInterface $authorization
     * @param RequestInterface $request
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        UrlInterface $urlBuilder,
        CollectionFactory $collectionFactory,
        AuthorizationInterface $authorization,
        RequestInterface $request,
        $name,
        $primaryFieldName,
        $requestFieldName,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $meta,
            $data
        );
        $this->urlBuilder = $urlBuilder;
        $this->collectionFactory = $collectionFactory;
        $this->authorization = $authorization;
        $this->request = $request;
    }

    /**
     * @return bool
     */
    private function isAllowed()
    {
        return (bool) ($this->authorization->isAllowed('Unbxd_ProductFeed::cron')
            && ($this->request->getFullActionName() == self::SYSTEM_CONFIG_EDIT_LAYOUT_HANDLER));
    }

    /**
     * {@inheritdoc}
     */
    public function getMeta()
    {
        $meta = parent::getMeta();
        if (!$this->isAllowed()
        ) {
            return $meta;
        }

        $meta = $this->customizeCronJobsModal($meta);
        $meta = $this->customizeCronJobsGrid($meta);

        return $meta;
    }

    /**
     * @param array $meta
     * @return array
     */
    private function customizeCronJobsModal(array $meta)
    {
        $meta['cron_jobs_modal']['arguments']['data']['config'] = [
            'isTemplate' => false,
            'componentType' => Component\Modal::NAME,
            'dataScope' => '',
            'provider' => 'unbxd_productfeed_cron_grid.unbxd_productfeed_cron_grid_data_source',
            'imports' => [
                'state' => '!index=jobs_listing:responseStatus'
            ],
            'options' => [
                'title' => __('Related Cron Jobs'),
                'buttons' => [
                    [
                        'component' => 'Magento_Ui/js/form/components/button',
                        'text' => 'Go To Separate Layout',
                        'class' => 'action-primary',
                        'actions' => [
                            [
                                'targetName' => 'unbxd_productfeed_cron_grid_modal.unbxd_productfeed_cron_grid_modal.cron_jobs_modal.jobs_listing',
                                'actionName' => 'separateLayout'
                            ]
                        ]
                    ],
                    [
                        'component' => 'Magento_Ui/js/form/components/button',
                        'text' => 'Configure Cron Groups',
                        'class' => 'action-primary',
                        'actions' => [
                            [
                                'targetName' => 'unbxd_productfeed_cron_grid_modal.unbxd_productfeed_cron_grid_modal.cron_jobs_modal.jobs_listing',
                                'actionName' => 'configureGroups'
                            ]
                        ]
                    ],
                    [
                        'component' => 'Magento_Ui/js/form/components/button',
                        'text' => __('Clear Jobs'),
                        'class' => 'action-primary',
                        'actions' => [
                            [
                                'targetName' => 'unbxd_productfeed_cron_grid_modal.unbxd_productfeed_cron_grid_modal.cron_jobs_modal.jobs_listing',
                                'actionName' => 'clearJobs'
                            ]
                        ]
                    ]
                ],
            ],
        ];

        return $meta;
    }

    /**
     * @param array $meta
     * @return array
     */
    private function customizeCronJobsGrid(array $meta)
    {
        $meta['cron_jobs_modal']['children']['jobs_listing'] = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'component' => 'Unbxd_ProductFeed/js/components/cron-jobs-insert-listing',
                        'componentType' => Component\Container::NAME,
                        'autoRender' => false,
                        'dataScope' => 'unbxd_productfeed_cron_grid',
                        'externalProvider' => 'unbxd_productfeed_cron_grid.unbxd_productfeed_cron_grid_data_source',
                        'selectionsProvider' => '${ $.ns }.${ $.ns }.unbxd_productfeed_cron_grid_columns.ids',
                        'ns' => 'unbxd_productfeed_cron_grid',
                        'render_url' => $this->urlBuilder->getUrl('mui/index/render'),
                        'immediateUpdateBySelection' => true,
                        'behaviourType' => 'simple',
                        'externalFilterMode' => true,
                        'dataLinks' => [
                            'imports' => false,
                            'exports' => true,
                        ],
                        'formProvider' => 'ns = ${ $.namespace }, index = unbxd_productfeed_cron_grid',
                        'groupCode' => 'cron_jobs_listing',
                        'groupName' => 'Cron Jobs Listing',
                        'groupSortOrder' => 0,
                        'clearJobsUrl' =>
                            $this->urlBuilder->getUrl('unbxd_productfeed/cron/delete'),
                        'separateLayoutUrl' =>
                            $this->urlBuilder->getUrl('unbxd_productfeed/cron/view'),
                        'configureGroupsUrl' =>
                            $this->urlBuilder->getUrl('adminhtml/system_config/edit/section/system'),
                        'loading' => false,
                        'imports' => [],
                        'exports' => []
                    ],
                ],
            ]
        ];

        return $meta;
    }

    /**
     * @return \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
     */
    public function getCollection()
    {
        if (!$this->collection) {
            /** @var Collection collection */
            $this->collection = $this->collectionFactory->create()->filterCollectionByRelatedJobs();
        }

        return $this->collection;
    }
}