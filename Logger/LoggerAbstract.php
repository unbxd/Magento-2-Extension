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

use Unbxd\ProductFeed\Logger\LoggerInterface;
use Unbxd\ProductFeed\Logger\OptionsListConstants;
use Magento\Framework\Debug;

/**
 * Class LoggerAbstract
 * @package Unbxd\ProductFeed\Logger
 */
abstract class LoggerAbstract implements LoggerInterface
{
    /**
     * @var int
     */
    private $timer;

    /**
     * @var bool
     */
    private $logAll;

    /**
     * @var bool
     */
    private $logCallStack;

    /**
     * LoggerAbstract constructor.
     * @param bool $logAll
     * @param bool $logCallStack
     */
    public function __construct(
        $logAll = OptionsListConstants::LOGGER_LOG_EVERYTHING,
        $logCallStack = OptionsListConstants::LOGGER_INCLUDE_STACKTRACE
    ) {
        $this->logAll = $logAll;
        $this->logCallStack = $logCallStack;
    }

    /**
     * {@inheritdoc}
     */
    public function startTimer()
    {
        $this->timer = microtime(true);
    }

    /**
     * @return string
     */
    public function getTime()
    {
        return sprintf('%.4f', microtime(true) - $this->timer);
    }

    /**
     * Get formatted statistics message
     *
     * @param null $type
     * @param string $extraMessage
     * @return string
     */
    public function getStats($extraMessage = '', $type = null)
    {
        $message = 'Process ID ' . getmypid() . ' | ';
        $nl = "\n";

        if ($extraMessage) {
            $message .= $extraMessage . ' | ';
        }

        // @TODO - need type?
        if ($type) {
            switch ($type) {
                case '':
                    $message .= '' . $nl;
                    break;
            }
        }

        $message .= 'EXECUTION TIME: ' . $this->getTime();

        if ($this->logCallStack) {
            $message .= 'TRACE: ' . Debug::backtrace(true, false);
        }

        return $message;
    }

    /**
     * Return human readable debug trace.
     *
     * @param $trace
     * @return string
     */
    public function getFormattedLogTrace($trace)
    {
        $output = '';
        $lineNumber = 1;
        foreach ($trace as &$info) {
            $output .= $lineNumber++ . ': ' . $info . "\n";
        }

        return $output;
    }
}