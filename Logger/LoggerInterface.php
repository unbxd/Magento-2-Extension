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
 * Product feed logger interface
 *
 * Interface LoggerInterface
 * @package Unbxd\ProductFeed\Logger
 */
interface LoggerInterface
{
    /**#@+
     * Constants to identify log type record
     */
    const DEBUG = 'DEBUG';
    const INFO = 'INFO';
    const ERROR = 'ERROR';
    const CRITICAL = 'CRITICAL';
    /**#@-*/

    /**
     * Adds info log record
     *
     * @param string $string
     * @return mixed
     */
    public function info($string);

    /**
     * Adds debug log record
     *
     * @param string $string
     * @return mixed
     */
    public function debug($string);

    /**
     * Adds error log record
     *
     * @param string $string
     * @return mixed
     */
    public function error($string);

    /**
     * @param \Exception $e
     * @return void
     */
    public function critical(\Exception $e);

    /**
     * @return void
     */
    public function startTimer();

    /**
     * @return string
     */
    public function getTime();

    /**
     * @param string $extraMessage
     * @param null $type
     * @return mixed
     */
    public function logStats($extraMessage = '', $type = null);
}
