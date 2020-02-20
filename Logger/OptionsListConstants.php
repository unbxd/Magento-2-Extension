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
namespace Unbxd\ProductFeed\Logger;

/**
 * Class OptionsListConstants
 * @package Unbxd\ProductFeed\Logger
 */
class OptionsListConstants
{
    /**
     * Logger sub directory
     */
    const LOGGER_SUB_DIR = 'unbxd';

    /**
     * Logger output
     */
    const LOGGER_OUTPUT = 'file';

    /**
     * Default log file type
     */
    const LOGGER_TYPE_DEFAULT = 'default';

    /**
     * Indexing log file type
     */
    const LOGGER_TYPE_INDEXING = 'indexing';

    /**
     * Feed log file type
     */
    const LOGGER_TYPE_FEED = 'feed';

    /**
     * Flag to check if everything must be logged or not
     */
    const LOGGER_LOG_EVERYTHING = true;

    /**
     * Flag to check if include stacktrace
     */
    const LOGGER_INCLUDE_STACKTRACE = false;
}