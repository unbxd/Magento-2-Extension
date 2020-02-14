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
namespace Unbxd\ProductFeed\Helper;

use Unbxd\ProductFeed\Helper\Data as HelperData;
use Unbxd\ProductFeed\Model\FeedView;

/**
 * Class Feed
 * @package Unbxd\ProductFeed\Helper
 */
class Feed extends HelperData
{
    /**
     * Core config path/value pairs related to feed process
     *
     * is full catalog was sync or not
     */
    const FEED_PATH_FULL_STATE_FLAG = 'unbxd_catalog/feed/full_state_flag';
    /**
     * is separate product was sync or not
     */
    const FEED_PATH_INCREMENTAL_STATE_FLAG = 'unbxd_catalog/feed/incremental_state_flag';
    /**
     * flag to prevent duplicate full catalog sync process
     */
    const FEED_PATH_FULL_LOCK_FLAG = 'unbxd_catalog/feed/full_lock_flag';
    /**
     * full catalog sync lock time
     */
    const FEED_PATH_FULL_LOCK_TIME = 'unbxd_catalog/feed/full_lock_time';
    /**
     * full or incremental
     */
    const FEED_PATH_LAST_OPERATION_TYPE = 'unbxd_catalog/feed/last_operation_type';
    /**
     * last sync datetime
     */
    const FEED_PATH_LAST_DATETIME = 'unbxd_catalog/feed/last_datetime';
    /**
     * last sync status
     */
    const FEED_PATH_LAST_STATUS = 'unbxd_catalog/feed/last_status';
    /*
     * last sync upload id (from response if any)
     */
    const FEED_PATH_LAST_UPLOAD_ID = 'unbxd_catalog/feed/last_upload_id';
    /**
     * uploaded feed size
     */
    const FEED_PATH_UPLOADED_SIZE = 'unbxd_catalog/feed/uploaded_size';
    /**
     * flag to detect if feed was generated for download
     */
    const FEED_PATH_GENERATED_FOR_DOWNLOAD = 'unbxd_catalog/feed/generated_for_download';

    /**
     * Default configuration core config data fields
     *
     * @var array
     */
    private $defaultConfigDataFields = [
        self::FEED_PATH_FULL_STATE_FLAG => '',
        self::FEED_PATH_INCREMENTAL_STATE_FLAG => '',
        self::FEED_PATH_FULL_LOCK_FLAG => '',
        self::FEED_PATH_FULL_LOCK_TIME => '',
        self::FEED_PATH_LAST_OPERATION_TYPE => '',
        self::FEED_PATH_LAST_DATETIME => '',
        self::FEED_PATH_LAST_STATUS => '',
        self::FEED_PATH_LAST_UPLOAD_ID => '',
        self::FEED_PATH_UPLOADED_SIZE => '',
        self::FEED_PATH_GENERATED_FOR_DOWNLOAD => ''
    ];

    /**
     * @return bool
     */
    public function isFullCatalogSynchronized()
    {
        return (bool) $this->getConfigValue(self::FEED_PATH_FULL_STATE_FLAG);
    }

    /**
     * @return bool
     */
    public function isIncrementalProductSynchronized()
    {
        return (bool) $this->getConfigValue(self::FEED_PATH_INCREMENTAL_STATE_FLAG);
    }

    /**
     * @return bool
     */
    public function isFullSynchronizationLocked()
    {
        return (bool) $this->getConfigValue(self::FEED_PATH_FULL_LOCK_FLAG);
    }

    /**
     * @return string
     */
    public function getFullSynchronizationLockedTime()
    {
        return $this->getConfigValue(self::FEED_PATH_FULL_LOCK_TIME);
    }

    /**
     * @return string
     */
    public function getLastSynchronizationOperationType()
    {
        return $this->getConfigValue(self::FEED_PATH_LAST_OPERATION_TYPE);
    }

    /**
     * @return string
     */
    public function getLastSynchronizationDatetime()
    {
        return $this->getConfigValue(self::FEED_PATH_LAST_DATETIME);
    }

    /**
     * @return string
     */
    public function getLastSynchronizationStatus()
    {
        return $this->getConfigValue(self::FEED_PATH_LAST_STATUS);
    }

    /**
     * @return string
     */
    public function getLastUploadId()
    {
        return $this->getConfigValue(self::FEED_PATH_LAST_UPLOAD_ID);
    }

    /**
     * @return string
     */
    public function getUploadedSize()
    {
        return $this->getConfigValue(self::FEED_PATH_UPLOADED_SIZE);
    }

    /**
     * @return string
     */
    public function getIsGeneratedForDownload()
    {
        return $this->getConfigValue(self::FEED_PATH_GENERATED_FOR_DOWNLOAD);
    }

    /**
     * @param $status
     * @return $this
     */
    public function setFullCatalogSynchronizedStatus($status)
    {
        $this->updateConfigValue(self::FEED_PATH_FULL_STATE_FLAG, $status);

        return $this;
    }

    /**
     * @param $status
     * @return $this
     */
    public function setIncrementalProductSynchronizedStatus($status)
    {
        $this->updateConfigValue(self::FEED_PATH_INCREMENTAL_STATE_FLAG, $status);

        return $this;
    }

    /**
     * @param $status
     * @return $this
     */
    public function setFullSynchronizationLocked($status)
    {
        $this->updateConfigValue(self::FEED_PATH_FULL_LOCK_FLAG, $status);

        return $this;
    }

    /**
     * @param $time
     * @return $this
     */
    public function setFullSynchronizationLockedTime($time)
    {
        $this->updateConfigValue(self::FEED_PATH_FULL_LOCK_TIME, $time);

        return $this;
    }

    /**
     * @param $type
     * @return $this
     */
    public function setLastSynchronizationOperationType($type)
    {
        $this->updateConfigValue(self::FEED_PATH_LAST_OPERATION_TYPE, $type);

        return $this;
    }

    /**
     * @param $datetime
     * @return $this
     */
    public function setLastSynchronizationDatetime($datetime)
    {
        $this->updateConfigValue(self::FEED_PATH_LAST_DATETIME, $datetime);

        return $this;
    }

    /**
     * @param $status
     * @return $this
     */
    public function setLastSynchronizationStatus($status)
    {
        $this->updateConfigValue(self::FEED_PATH_LAST_STATUS, $status);

        return $this;
    }

    /**
     * @param $uploadId
     * @return $this
     */
    public function setLastUploadId($uploadId)
    {
        $this->updateConfigValue(self::FEED_PATH_LAST_UPLOAD_ID, $uploadId);

        return $this;
    }

    /**
     * @param $size
     * @return $this
     */
    public function setUploadedSize($size)
    {
        $this->updateConfigValue(self::FEED_PATH_UPLOADED_SIZE, $size);

        return $this;
    }

    /**
     * @param int $status
     * @return $this
     */
    public function setIsGeneratedForDownload($status)
    {
        $this->updateConfigValue(self::FEED_PATH_GENERATED_FOR_DOWNLOAD, $status);

        return $this;
    }

    /**
     * @return bool
     */
    public function isLastSynchronizationSuccess()
    {
        return (bool) ($this->getLastSynchronizationStatus() == FeedView::STATUS_COMPLETE);
    }

    /**
     * @return bool
     */
    public function isLastSynchronizationProcessing()
    {
        return (bool) ($this->getLastSynchronizationStatus() == FeedView::STATUS_INDEXING);
    }

    /**
     * @return array
     */
    public function getDefaultConfigFields()
    {
        return $this->defaultConfigDataFields;
    }

    /**
     * Reset config value to init state
     *
     * @return $this
     */
    public function resetConfigFields()
    {
        foreach ($this->defaultConfigDataFields as $path => $value) {
            $this->updateConfigValue($path, $value);
        }

        return $this;
    }
}