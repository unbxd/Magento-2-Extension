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
namespace Unbxd\ProductFeed\Helper;

use Magento\Framework\File\Size as FileSizeService;

/**
 * Class Service
 * @package Unbxd\ProductFeed\Helper
 */
class Service
{
    /**
     * Ini parameters
     */
    const INI_PARAM_MEMORY_LIMIT = 'memory_limit';
    const INI_PARAM_POST_MAX_SIZE = 'post_max_size';

    /**
     * @var FileSizeService
     */
    protected $fileSizeService;

    /**
     * Service constructor.
     * @param FileSizeService $fileSizeService
     */
    public function __construct(
        FileSizeService $fileSizeService
    ) {
        $this->fileSizeService = $fileSizeService;
    }

    /**
     * Gets the value of a configuration option
     *
     * @param $param
     * @return string|null
     */
    private function iniGet($param)
    {
        if (function_exists('ini_get')) {
            return trim(ini_get($param));
        }

        return null;
    }

    /**
     * Sets the value of a configuration option
     *
     * @param $param
     * @param $value
     * @return $this
     */
    private function iniSet($param, $value)
    {
        if (function_exists('ini_set')) {
            try {
                @ini_set(trim($param), trim($value));
            } catch (\Exception $e) {
                // catch exception
            }
        }

        return $this;
    }

    /**
     * Get post max size
     *
     * @return string
     */
    public function getPostMaxSize()
    {
        return $this->iniGet(self::INI_PARAM_POST_MAX_SIZE);
    }

    /**
     * Get memory limit
     *
     * @return string
     */
    public function getMemoryLimit()
    {
        return $this->iniGet(self::INI_PARAM_MEMORY_LIMIT);
    }

    /**
     * Converts memory value (e.g. 64M, 129K) to bytes.
     *
     * Case insensitive value might be used.
     *
     * @param string $memoryValue
     * @return int
     */
    private function convertToByte($memoryValue)
    {
        if (stripos($memoryValue, 'G') !== false) {
            return (int) $memoryValue * pow(1024, 3);
        } elseif (stripos($memoryValue, 'M') !== false) {
            return (int) $memoryValue * 1024 * 1024;
        } elseif (stripos($memoryValue, 'K') !== false) {
            return (int) $memoryValue * 1024;
        }

        return (int) $memoryValue;
    }

    /**
     * Get file needed post size
     *
     * @return int
     */
    public function getNeedPostMaxSize()
    {
        //@TODO - implement
        return 0;
    }

    /**
     * Checks whether post max size is reached.
     *
     * @return bool
     */
    public function isPostMaxSizeReached()
    {
        $postMaxSize = $this->convertToByte($this->getPostMaxSize());
        $requiredSize = $this->getNeedPostMaxSize();
        if ($postMaxSize === -1) {
            // a limit of -1 means no limit: http://www.php.net/manual/en/ini.core.php#ini.post-max-size
            return false;
        }

        return $requiredSize > $postMaxSize;
    }

    /**
     * Update post max size by new value
     *
     * @param string $value
     * @return $this
     */
    public function updatePostMaxSize($value)
    {
        $postMaxSize = $this->convertToByte($this->getPostMaxSize());
        $convertedValue = $this->convertToByte($value);
        if ($postMaxSize != -1 && $postMaxSize < $convertedValue) {
            $this->iniSet(self::INI_PARAM_POST_MAX_SIZE, $value);
        }

        return $this;
    }

    /**
     * Get file needed memory size
     *
     * @return int
     */
    public function getNeedMemorySize()
    {
        //@TODO - implement
        return 0;
    }

    /**
     * Checks whether memory limit is reached.
     *
     * @return bool
     */
    public function isMemoryLimitReached()
    {
        $memoryLimit = $this->convertToByte($this->getMemoryLimit());
        $requiredMemory = $this->getNeedMemorySize();
        if ($memoryLimit === -1) {
            // a limit of -1 means no limit: http://www.php.net/manual/en/ini.core.php#ini.memory-limit
            return false;
        }

        return memory_get_usage(true) + $requiredMemory > $memoryLimit;
    }

    /**
     * Update memory limit by new value
     *
     * @param string $value
     * @return $this
     */
    public function updateMemoryLimit($value = '756M')
    {
        $memoryLimit = $this->convertToByte($this->getMemoryLimit());
        $convertedValue = $this->convertToByte($value);
        if ($memoryLimit != -1 && $memoryLimit < $convertedValue) {
            $this->iniSet(self::INI_PARAM_MEMORY_LIMIT, $value);
        }

        return $this;
    }
}