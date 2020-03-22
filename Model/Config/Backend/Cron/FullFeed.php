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
namespace Unbxd\ProductFeed\Model\Config\Backend\Cron;

use Unbxd\ProductFeed\Model\Config\Backend\Cron;

/**
 * Class Manually
 * @package Unbxd\ProductFeed\Model\Config\Backend\Cron
 */
class FullFeed extends Cron
{
    const CRON_FULL_STRING_PATH = 'crontab/unbxd/jobs/unbxd_full_product_feed_upload/schedule/cron_expr';

    const CRON_FULL_MODEL_PATH = 'crontab/unbxd/jobs/unbxd_full_product_feed_upload/run/model';

    const XML_PATH_CRON_FULL_ENABLED = 'groups/cron/groups/full_feed_settings/fields/enabled/value';

    const XML_PATH_CRON_FULL_TYPE = 'groups/cron/groups/full_feed_settings/fields/cron_type/value';

    const XML_PATH_CRON_FULL_TYPE_TEMPLATE_TIME = 'groups/cron/groups/full_feed_settings/fields/cron_type_template_time/value';

    const XML_PATH_CRON_FULL_TYPE_TEMPLATE_FREQUENCY = 'groups/cron/groups/full_feed_settings/fields/cron_type_template_frequency/value';

    /**
     * @return mixed
     */
    public function getIsCronIsEnabled()
    {
        return $this->getData(self::XML_PATH_CRON_FULL_ENABLED);
    }

    /**
     * @return mixed
     */
    public function getCronType()
    {
        return $this->getData(self::XML_PATH_CRON_FULL_TYPE);
    }
}