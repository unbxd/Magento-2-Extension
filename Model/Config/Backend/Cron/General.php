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
class General extends Cron
{
    const CRON_GENERAL_STRING_PATH = 'crontab/unbxd/jobs/unbxd_product_feed_upload/schedule/cron_expr';

    const CRON_GENERAL_MODEL_PATH = 'crontab/unbxd/jobs/unbxd_product_feed_upload/run/model';

    const XML_PATH_CRON_GENERAL_ENABLED = 'groups/cron/groups/general_settings/fields/enabled/value';

    const XML_PATH_CRON_GENERAL_TYPE = 'groups/cron/groups/general_settings/fields/cron_type/value';

    const XML_PATH_CRON_GENERAL_TYPE_TEMPLATE_TIME = 'groups/cron/groups/general_settings/fields/cron_type_template_time/value';

    const XML_PATH_CRON_GENERAL_TYPE_TEMPLATE_FREQUENCY = 'groups/cron/groups/general_settings/fields/cron_type_template_frequency/value';

    /**
     * @return mixed
     */
    public function getIsCronIsEnabled()
    {
        return $this->getData(self::XML_PATH_CRON_GENERAL_ENABLED);
    }

    /**
     * @return mixed
     */
    public function getCronType()
    {
        return $this->getData(self::XML_PATH_CRON_GENERAL_TYPE);
    }
}