<?php
/**
 * Copyright (c) 2019 Unbxd Inc.
 */

/**
 * Init development:
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 */
namespace Unbxd\ProductFeed\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Framework\Data\Form\FormKey;
use Unbxd\ProductFeed\Helper\Data as HelperData;
use Unbxd\ProductFeed\Model\Serializer;
use Magento\Framework\App\ObjectManager;

/**
 * Class Common
 * @package Unbxd\ProductFeed\Block\Adminhtml
 */
class Common extends Template
{
    /**
     * @var FormKey
     */
    protected $formKey;

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * Common constructor.
     * @param Template\Context $context
     * @param HelperData $helperData
     * @param array $data
     * @param Serializer|null $serializer
     */
    public function __construct(
        Template\Context $context,
        HelperData $helperData,
        array $data = [],
        Serializer $serializer = null
    ) {
        parent::__construct($context, $data);
        $this->formKey = $context->getFormKey();
        $this->helperData = $helperData;
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(Serializer::class);
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getConfig()
    {
        return [
            'isActionAllow' => $this->isActionAllow(),
            'isCronConfigured' => $this->isCronConfigured(),
            'url' => [
                'cronJobs' => $this->getCronJobsActionUrl(),
                'fullSync' => $this->getFullSyncActionUrl(),
                'incrementalSync' => $this->getIncrementalSyncActionUrl(),
                'generate' => $this->getGenerateProductFeedActionUrl()
            ]
        ];
    }

    /**
     * Returns config in JSON format.
     *
     * @return bool|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getJsonConfig()
    {
        return $this->serializer->serialize($this->getConfig());
    }

    /**
     * Retrieve Session Form Key
     *
     * @return string
     */
    public function getFormKey()
    {
        return $this->formKey->getFormKey();
    }

    /**
     * Check whether action is allow or not
     *
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function isActionAllow()
    {
        return $this->helperData->isAuthorizationCredentialsSetup($this->getStore());
    }

    /**
     * Check whether related cron job is configured or not
     *
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function isCronConfigured()
    {
        return $this->helperData->isCronConfigured($this->getStore());
    }

    /**
     * Create action url by path
     *
     * @param string $path
     * @return string
     */
    private function getActionUrl($path = '')
    {
        return $this->getUrl($path,
            [
                '_secure' => $this->getRequest()->isSecure()
            ]
        );
    }

    /**
     * Get url for check if cron is running
     *
     * @return string
     */
    private function getCronJobsActionUrl()
    {
        return $this->getActionUrl('mui/index/render/namespace/unbxd_productfeed_cron_grid');
    }

    /**
     * Get url for full product catalog synchronization
     *
     * @return string
     */
    private function getFullSyncActionUrl()
    {
        return $this->getActionUrl('unbxd_productfeed/feed/full');
    }

    /**
     * Get url for incremental product catalog synchronization
     *
     * @return string
     */
    private function getIncrementalSyncActionUrl()
    {
        return $this->getActionUrl('unbxd_productfeed/feed/incremental');
    }

    /**
     * Get url for generate product feed
     *
     * @return string
     */
    private function getGenerateProductFeedActionUrl()
    {
        return $this->getActionUrl('unbxd_productfeed/feed/generate');
    }

    /**
     * Retrieve current store
     *
     * @param string $store
     * @return \Magento\Store\Api\Data\StoreInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getStore($store = '')
    {
        return $this->_storeManager->getStore($store);
    }
}
