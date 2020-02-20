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
namespace Unbxd\ProductFeed\Model\Config\Backend;

/**
 * Class Cron
 * @package Unbxd\ProductFeed\Model\Config\Backend
 */
class Cron extends \Magento\Framework\App\Config\Value
{
    const CRON_STRING_PATH = 'crontab/unbxd/jobs/unbxd_product_feed_upload/schedule/cron_expr';

    const CRON_MODEL_PATH = 'crontab/unbxd/jobs/unbxd_product_feed_upload/run/model';

    const XML_PATH_CRON_ENABLED = 'groups/cron/fields/enabled/value';

    const XML_PATH_CRON_TYPE = 'groups/cron/fields/cron_type/value';

    const XML_PATH_CRON_TYPE_TEMPLATE_TIME = 'groups/cron/fields/cron_type_template_time/value';

    const XML_PATH_CRON_TYPE_TEMPLATE_FREQUENCY = 'groups/cron/fields/cron_type_template_frequency/value';

    /**
     * @var \Magento\Framework\App\Config\ValueFactory
     */
    protected $configValueFactory;

    /**
     * @var string
     */
    protected $runModelPath = '';

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\App\Config\ValueFactory $configValueFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param string $runModelPath
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Config\ValueFactory $configValueFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        $runModelPath = '',
        array $data = []
    ) {
        $this->runModelPath = $runModelPath;
        $this->configValueFactory = $configValueFactory;
        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * @return mixed
     */
    public function getIsCronIsEnabled()
    {
        return $this->getData(self::XML_PATH_CRON_ENABLED);
    }

    /**
     * @return mixed
     */
    public function getCronType()
    {
        return $this->getData(self::XML_PATH_CRON_TYPE);
    }

    /**
     * @param string $cronExprString
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function updateConfigValues($cronExprString = '')
    {
        try {
            $this->configValueFactory->create()->load(
                self::CRON_STRING_PATH,
                'path'
            )->setValue(
                $cronExprString
            )->setPath(
                self::CRON_STRING_PATH
            )->save();

            $this->configValueFactory->create()->load(
                self::CRON_MODEL_PATH,
                'path'
            )->setValue(
                $this->runModelPath
            )->setPath(
                self::CRON_MODEL_PATH
            )->save();
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__(
                'Can\'t save the cron expression: %1', $cronExprString
            ));
        }
    }
}